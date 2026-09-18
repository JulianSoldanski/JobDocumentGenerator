<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStage;
use App\Enums\DocumentType;
use App\Http\Resources\ApplicationResource;
use App\Models\Application;
use App\Models\Document;
use App\Models\StageEvent;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Der Tracker: jede Bewerbung mit ihren Stufen, ihrem Feedback und den
 * Dokumenten, die für sie entstanden sind.
 *
 * Stufen werden nie überschrieben, sondern als Ereignis angehängt (Prinzip 4)
 * — darauf baut die Statistik.
 */
class ApplicationController extends Controller
{
    /**
     * Laufende Bewerbungen zuerst, die mit der jüngsten Bewegung oben; die
     * abgesagten darunter.
     */
    public function index(Request $request): Response
    {
        $applications = $request->user()->applications()->with('stageEvents')->get()
            ->sortByDesc(fn (Application $application): string => ($application->isRejected() ? '0' : '1')
                .($application->currentStageSince()?->toIso8601String() ?? ''))
            ->values();

        return Inertia::render('applications/index', [
            'applications' => ApplicationResource::collection($applications)->resolve(),
            'stages' => self::stages(),
        ]);
    }

    public function show(Application $application): Response
    {
        $this->authorize('view', $application);

        $application->load('stageEvents');

        // Je Typ die jüngste Fassung — sie ist der Snapshot dieser Bewerbung.
        $documents = $application->documents()
            ->whereIn('type', [DocumentType::Cv->value, DocumentType::Letter->value])
            ->latest('updated_at')
            ->get()
            ->unique(fn (Document $document): string => $document->type->value)
            ->mapWithKeys(fn (Document $document): array => [
                $document->type->value => ['id' => $document->id, 'version' => $document->version, 'edited' => false],
            ]);

        return Inertia::render('applications/show', [
            'application' => ApplicationResource::make($application)->resolve(),
            'history' => $application->stageEvents->map(fn (StageEvent $event): array => [
                'id' => $event->id,
                'stage' => $event->stage->value,
                'occurred_at' => $event->occurred_at->toIso8601String(),
            ])->all(),
            'documents' => $documents->all(),
            'session_id' => $application->generatorSessions()->latest('updated_at')->value('id'),
            'stages' => self::stages(),
        ]);
    }

    /**
     * Für Bewerbungen, die außerhalb des Tools entstanden sind — mit
     * Startstufe und Datum.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company' => ['required', 'string', 'max:255'],
            'position' => ['required', 'string', 'max:255'],
            'job_url' => ['nullable', 'url', 'max:2048'],
            'stage' => ['required', Rule::enum(ApplicationStage::class)],
            'date' => ['required', 'date', 'before_or_equal:today'],
        ]);

        $user = $request->user();
        $this->ensureUnique($user, $validated['company'], $validated['position']);

        $application = $user->applications()->create(Arr::only($validated, ['company', 'position', 'job_url']));
        $application->moveTo(ApplicationStage::from($validated['stage']), self::moment($validated['date']));

        return to_route('applications.show', $application);
    }

    public function update(Request $request, Application $application): RedirectResponse
    {
        $this->authorize('update', $application);

        $validated = $request->validate([
            'company' => ['required', 'string', 'max:255'],
            'position' => ['required', 'string', 'max:255'],
            'job_url' => ['nullable', 'url', 'max:2048'],
            'applied_on' => ['nullable', 'date'],
            'feedback' => ['nullable', 'string', 'max:10000'],
        ]);

        $this->ensureUnique($request->user(), $validated['company'], $validated['position'], $application);

        $application->update($validated);

        return back();
    }

    /**
     * Ein Stufenwechsel — auch nachgetragen, aber nie vor den letzten
     * Eintrag: Sonst stünde die Historie in sich widersprüchlich da.
     */
    public function stage(Request $request, Application $application): RedirectResponse
    {
        $this->authorize('update', $application);

        $last = $application->stageEvents->last()?->occurred_at;

        $validated = $request->validate([
            'stage' => ['required', Rule::enum(ApplicationStage::class), Rule::notIn([$application->current_stage->value])],
            'date' => array_filter([
                'required', 'date', 'before_or_equal:today',
                $last ? 'after_or_equal:'.$last->toDateString() : null,
            ]),
        ], [
            'stage.not_in' => 'Die Bewerbung steht schon auf dieser Stufe.',
            'date.after_or_equal' => 'Der Wechsel kann nicht vor dem letzten Eintrag im Verlauf liegen.',
        ]);

        $application->moveTo(ApplicationStage::from($validated['stage']), self::moment($validated['date']));

        return back();
    }

    /**
     * Nach einer Absage zurück auf die zuletzt erreichte Stufe.
     */
    public function reactivate(Application $application): RedirectResponse
    {
        $this->authorize('update', $application);

        if ($application->isRejected()) {
            $application->reactivate();
        }

        return back();
    }

    public function destroy(Application $application): RedirectResponse
    {
        $this->authorize('delete', $application);

        $application->delete();

        return to_route('applications.index');
    }

    /**
     * Die Stufen mit ihren Namen — eine Quelle für Liste, Auswahl und Verlauf.
     *
     * @return array<int, array{value: string, label: string}>
     */
    private static function stages(): array
    {
        return array_map(
            fn (ApplicationStage $stage): array => ['value' => $stage->value, 'label' => $stage->label()],
            ApplicationStage::cases(),
        );
    }

    /**
     * Ein Datum aus dem Formular als Zeitpunkt: heute ist jetzt, ein
     * nachgetragener Tag zählt bis zu seinem Ende — so bleibt die Reihenfolge
     * im Verlauf auch bei mehreren Einträgen am selben Tag stimmig.
     */
    private static function moment(string $date): Carbon
    {
        $day = Carbon::parse($date);

        return $day->isToday() ? Carbon::now() : $day->endOfDay();
    }

    private function ensureUnique(User $user, string $company, string $position, ?Application $except = null): void
    {
        $taken = $user->applications()
            ->where('company_key', Application::key($company))
            ->where('position_key', Application::key($position))
            ->when($except, fn ($query) => $query->whereKeyNot($except->getKey()))
            ->exists();

        if ($taken) {
            throw ValidationException::withMessages([
                'position' => 'Für dieses Unternehmen gibt es schon eine Bewerbung auf diese Position.',
            ]);
        }
    }
}
