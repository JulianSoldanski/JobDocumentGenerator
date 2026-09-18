<?php

namespace App\Http\Controllers;

use App\Enums\QueueItemStatus;
use App\Models\QueueItem;
use App\Support\JobPosting\PostingFetcher;
use App\Support\JobPosting\PostingFetchException;
use App\Support\JobPosting\PostingUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Die Sammelstelle für Stellen, die noch nicht bearbeitet sind.
 */
class QueueController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('queue/index', [
            'items' => $user->queueItems()->workOrder()->get()->map(fn (QueueItem $item): array => [
                'id' => $item->id,
                'url' => $item->url,
                'title' => $item->title,
                'note' => $item->note,
                'status' => $item->status->value,
                'created_at' => $item->created_at?->toIso8601String(),
                'application_id' => $item->application_id,
            ])->all(),
            'capture_url' => route('queue.capture', ['token' => $user->captureToken()]),
        ]);
    }

    /**
     * Eine Adresse von Hand eintragen, mit optionaler Notiz.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'url:http,https', 'max:500'],
            'note' => ['nullable', 'string', 'max:500'],
        ], [
            'url.url' => 'Das ist keine vollständige Web-Adresse.',
        ]);

        $url = PostingUrl::clean($validated['url']);

        if ($request->user()->queueItems()->where('url', $url)->exists()) {
            throw ValidationException::withMessages(['url' => 'Diese Stelle ist schon in der Queue.']);
        }

        $request->user()->queueItems()->create(['url' => $url, 'note' => (string) ($validated['note'] ?? '')]);

        return back();
    }

    /**
     * Überspringen — oder wieder öffnen, falls es doch interessiert.
     */
    public function update(Request $request, QueueItem $item): RedirectResponse
    {
        $this->authorize('update', $item);

        $validated = $request->validate([
            'status' => ['required', Rule::in([QueueItemStatus::Open->value, QueueItemStatus::Skipped->value])],
        ]);

        $status = QueueItemStatus::from($validated['status']);
        $item->update(['status' => $status, 'processed_at' => $status->isOpen() ? null : now()]);

        return back();
    }

    public function destroy(QueueItem $item): RedirectResponse
    {
        $this->authorize('delete', $item);

        $item->delete();

        return back();
    }

    /**
     * „→ Generieren": öffnet die Stelle im Generator, die Anzeige schon
     * geladen. Gibt es für sie schon einen Arbeitsplatz, geht es dorthin.
     */
    public function generate(Request $request, QueueItem $item, PostingFetcher $fetcher): RedirectResponse
    {
        $this->authorize('update', $item);

        $user = $request->user();
        $session = $user->generatorSessions()->where('queue_item_id', $item->id)->latest('updated_at')->first();

        if ($session === null) {
            $session = $user->generatorSessions()->make(['job_url' => $item->url, 'queue_item_id' => $item->id]);

            try {
                $session->job_posting = $fetcher->fetch($item->url);
                $session->startTimer();
            } catch (PostingFetchException $e) {
                // Kein Abbruch: Die Adresse steht drin, die Anzeige fügt man ein.
                Inertia::flash('toast', ['type' => 'warning', 'message' => $e->getMessage()]);
            }

            $session->save();
        }

        if ($item->status->isOpen()) {
            $item->update(['status' => QueueItemStatus::InProgress]);
        }

        return to_route('generator.show', $session);
    }

    /**
     * Ein neuer Link fürs Bookmarklet — der alte gilt danach nicht mehr.
     */
    public function token(Request $request): RedirectResponse
    {
        $request->user()->regenerateCaptureToken();

        return back();
    }
}
