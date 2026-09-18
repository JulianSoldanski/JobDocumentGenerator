<?php

namespace App\Support;

use App\Enums\ApplicationStage;
use App\Models\Application;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Wertet die Stufen-Historie aus.
 *
 * Gerechnet wird nur mit angehängten Ereignissen (Prinzip 4) — deshalb lässt
 * sich jede dieser Fragen beantworten, ohne das Datenmodell anzufassen.
 *
 * „Erstellt" und „Versendet" zählen in Funnel und Absagen als eine Zeile:
 * Fachlich sind sie dieselbe Stufe, getrennt gezählt verzerren sie die Quote.
 */
class ApplicationStatistics
{
    private const FIRST_ROW = 'Erstellt / Versendet';

    private const NO_INTERVIEW = 'Kein Gespräch';

    /** Kürzer war keine Arbeit an der Bewerbung, nur ein Durchklicken. */
    private const MIN_EFFORT_SECONDS = 20;

    private const INTERVIEWS = [ApplicationStage::Interview1, ApplicationStage::Interview2, ApplicationStage::Interview3];

    /**
     * @param  Collection<int, Application>  $applications  mit geladenen `stageEvents`
     */
    public function __construct(private readonly Collection $applications) {}

    /**
     * @return array{total: int, running: int, rejected: int, interviewed: int}
     */
    public function summary(): array
    {
        return [
            'total' => $this->applications->count(),
            'running' => $this->applications->reject(fn (Application $application): bool => $application->isRejected())->count(),
            'rejected' => $this->applications->filter(fn (Application $application): bool => $application->isRejected())->count(),
            'interviewed' => $this->reached(ApplicationStage::Interview1),
        ];
    }

    /**
     * Wie viele Bewerbungen welche Stufe erreicht haben — gemessen an der
     * höchsten, auch wenn danach abgesagt wurde.
     *
     * @return array<int, array{label: string, count: int}>
     */
    public function funnel(): array
    {
        return [
            ['label' => self::FIRST_ROW, 'count' => $this->applications->count()],
            ...array_map(
                fn (ApplicationStage $stage): array => ['label' => $stage->label(), 'count' => $this->reached($stage)],
                self::INTERVIEWS,
            ),
        ];
    }

    /**
     * Wie weit jede Bewerbung gekommen ist — jede genau einmal gezählt, nach
     * ihrer höchsten Stufe. Anders als im Funnel ergeben die Teile das Ganze.
     *
     * @return array<int, array{label: string, count: int}>
     */
    public function furthest(): array
    {
        $counts = $this->applications->countBy(function (Application $application): string {
            $highest = $application->highestStageReached();

            return in_array($highest, self::INTERVIEWS, true) ? $highest->label() : self::NO_INTERVIEW;
        });

        return array_map(
            fn (string $label): array => ['label' => $label, 'count' => $counts[$label] ?? 0],
            [self::NO_INTERVIEW, ...array_map(fn (ApplicationStage $stage): string => $stage->label(), self::INTERVIEWS)],
        );
    }

    /**
     * Die Zeit, die im Generator in eine Bewerbung floss, getrennt danach, ob
     * sie zum ersten Gespräch führte oder ohne eines abgesagt wurde.
     *
     * Nur Bewerbungen mit gemessener Zeit (ab 20 Sekunden) und feststehendem
     * Ausgang zählen; wie viele deshalb fehlen, steht daneben.
     *
     * @return array{groups: array<int, array{label: string, invited: bool, median_seconds: float|null, applications: array<int, array{id: int, company: string, position: string, seconds: int}>}>, unmeasured: int, pending: int}
     */
    public function effort(): array
    {
        $measured = $this->applications->filter(fn (Application $application): bool => $application->research_seconds >= self::MIN_EFFORT_SECONDS);
        $invited = $measured->filter(fn (Application $application): bool => self::hasReached($application, ApplicationStage::Interview1));
        $declined = $measured->filter(fn (Application $application): bool => $application->isRejected() && ! self::hasReached($application, ApplicationStage::Interview1));

        return [
            'groups' => [
                self::effortGroup('Zum Gespräch eingeladen', true, $invited),
                self::effortGroup('Absage ohne Gespräch', false, $declined),
            ],
            'unmeasured' => $this->applications->count() - $measured->count(),
            'pending' => $measured->count() - $invited->count() - $declined->count(),
        ];
    }

    /**
     * Wie lange eine Bewerbung typischerweise in einer Stufe liegt. Nur
     * abgeschlossene Aufenthalte zählen — wer gerade wartet, weiß noch nicht,
     * wie lange. Median, weil einzelne Ausreißer den Durchschnitt unbrauchbar
     * machen.
     *
     * @return array<int, array{label: string, median_days: float|null, samples: int}>
     */
    public function durations(): array
    {
        $stays = [];

        foreach ($this->applications as $application) {
            $events = $application->stageEvents->values();

            foreach ($events as $position => $event) {
                $next = $events[$position + 1] ?? null;

                if ($next !== null && $event->stage->isLinear()) {
                    $stays[$event->stage->value][] = $event->occurred_at->diffInSeconds($next->occurred_at, true) / 86400;
                }
            }
        }

        return array_map(fn (ApplicationStage $stage): array => [
            'label' => $stage->label(),
            'median_days' => self::median($stays[$stage->value] ?? []),
            'samples' => count($stays[$stage->value] ?? []),
        ], ApplicationStage::linear());
    }

    /**
     * Jede Absage mit der Stufe, aus der heraus sie kam — samt den
     * Bewerbungen dahinter, damit sich danach filtern lässt.
     *
     * @return array<int, array{label: string, count: int, applications: array<int, array<string, mixed>>}>
     */
    public function rejections(): array
    {
        $groups = [self::FIRST_ROW => []];

        foreach (self::INTERVIEWS as $stage) {
            $groups[$stage->label()] = [];
        }

        foreach ($this->applications as $application) {
            $events = $application->stageEvents->values();

            foreach ($events as $position => $event) {
                if ($event->stage !== ApplicationStage::Rejected) {
                    continue;
                }

                $from = $events[$position - 1]->stage ?? ApplicationStage::Created;
                $label = in_array($from, [ApplicationStage::Created, ApplicationStage::Sent], true) ? self::FIRST_ROW : $from->label();

                $groups[$label][] = [
                    'id' => $application->id,
                    'company' => $application->company,
                    'position' => $application->position,
                    'rejected_at' => $event->occurred_at->toIso8601String(),
                ];
            }
        }

        return array_map(function (string $label, array $applications): array {
            usort($applications, fn (array $a, array $b): int => strcmp($b['rejected_at'], $a['rejected_at']));

            return ['label' => $label, 'count' => count($applications), 'applications' => $applications];
        }, array_keys($groups), $groups);
    }

    /**
     * Bewerbungen je Monat, lückenlos bis heute — gezählt nach dem ersten
     * Eintrag im Verlauf, nicht nach dem Anlegen des Datensatzes: Übernommene
     * Bewerbungen wurden später angelegt, als sie entstanden.
     *
     * @return array<int, array{month: string, count: int}>
     */
    public function months(): array
    {
        $starts = $this->applications
            ->map(fn (Application $application) => $application->stageEvents->first()->occurred_at ?? $application->created_at)
            ->filter();

        if ($starts->isEmpty()) {
            return [];
        }

        $counts = $starts->countBy(fn ($start): string => $start->format('Y-m'));
        $month = CarbonImmutable::parse($starts->min())->startOfMonth();
        $last = CarbonImmutable::now()->startOfMonth();
        $months = [];

        while ($month <= $last) {
            $months[] = ['month' => $month->format('Y-m'), 'count' => $counts[$month->format('Y-m')] ?? 0];
            $month = $month->addMonth();
        }

        return $months;
    }

    private function reached(ApplicationStage $stage): int
    {
        return $this->applications
            ->filter(fn (Application $application): bool => self::hasReached($application, $stage))
            ->count();
    }

    private static function hasReached(Application $application, ApplicationStage $stage): bool
    {
        return ($application->highestStageReached()?->index() ?? -1) >= $stage->index();
    }

    /**
     * @param  Collection<int, Application>  $applications
     * @return array{label: string, invited: bool, median_seconds: float|null, applications: array<int, array{id: int, company: string, position: string, seconds: int}>}
     */
    private static function effortGroup(string $label, bool $invited, Collection $applications): array
    {
        return [
            'label' => $label,
            'invited' => $invited,
            'median_seconds' => self::median($applications->map(fn (Application $application): float => $application->research_seconds)->values()->all()),
            'applications' => $applications->sortBy('research_seconds')->map(fn (Application $application): array => [
                'id' => $application->id,
                'company' => $application->company,
                'position' => $application->position,
                'seconds' => $application->research_seconds,
            ])->values()->all(),
        ];
    }

    /**
     * @param  array<int, float>  $values
     */
    private static function median(array $values): ?float
    {
        if ($values === []) {
            return null;
        }

        sort($values);
        $middle = intdiv(count($values), 2);

        return count($values) % 2 === 1
            ? $values[$middle]
            : ($values[$middle - 1] + $values[$middle]) / 2;
    }
}
