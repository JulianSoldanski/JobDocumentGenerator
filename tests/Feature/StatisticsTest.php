<?php

namespace Tests\Feature;

use App\Enums\ApplicationStage;
use App\Models\Application;
use App\Models\User;
use App\Support\ApplicationStatistics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Die Statistik rechnet nur mit der Stufen-Historie (Prinzip 4).
 */
class StatisticsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(now()->setDate(2026, 9, 18)->setTime(12, 0));
        $this->user = User::factory()->create();
    }

    /**
     * @param  array<int, array{0: ApplicationStage, 1: string}>  $history
     */
    private function application(array $history): Application
    {
        $application = Application::factory()->for($this->user)->create();

        foreach ($history as [$stage, $at]) {
            $application->moveTo($stage, now()->parse($at));
        }

        return $application;
    }

    private function statistics(): ApplicationStatistics
    {
        return new ApplicationStatistics($this->user->applications()->with('stageEvents')->get());
    }

    private function profile(): void
    {
        // Versendet, 4 Tage später 1. Gespräch, 6 Tage später Absage.
        $this->application([
            [ApplicationStage::Created, '2026-07-01'],
            [ApplicationStage::Sent, '2026-07-01 18:00'],
            [ApplicationStage::Interview1, '2026-07-05 18:00'],
            [ApplicationStage::Rejected, '2026-07-11 18:00'],
        ]);
        // Versendet, 10 Tage später Absage.
        $this->application([
            [ApplicationStage::Sent, '2026-09-01'],
            [ApplicationStage::Rejected, '2026-09-11'],
        ]);
        // Versendet und wartet noch.
        $this->application([[ApplicationStage::Sent, '2026-09-10']]);
    }

    /**
     * Eine Absage nach dem Gespräch zählt trotzdem als „Gespräch erreicht".
     */
    public function test_the_funnel_counts_the_highest_stage_reached(): void
    {
        $this->profile();

        $funnel = $this->statistics()->funnel();

        $this->assertSame(['Erstellt / Versendet', 3], [$funnel[0]['label'], $funnel[0]['count']]);
        $this->assertSame(['1. Gespräch', 1], [$funnel[1]['label'], $funnel[1]['count']]);
        $this->assertSame(0, $funnel[2]['count']);
    }

    /**
     * Median statt Durchschnitt, und nur abgeschlossene Aufenthalte — wer
     * gerade wartet, weiß noch nicht, wie lange.
     */
    public function test_durations_are_medians_of_finished_stays(): void
    {
        $this->profile();

        $sent = collect($this->statistics()->durations())->firstWhere('label', 'Versendet');

        // Abgeschlossen: 4 Tage und 10 Tage; die laufende Bewerbung zählt nicht.
        $this->assertSame(2, $sent['samples']);
        $this->assertEqualsWithDelta(7.0, $sent['median_days'], 0.01);
    }

    public function test_rejections_are_grouped_by_the_stage_they_came_from(): void
    {
        $this->profile();

        $rejections = collect($this->statistics()->rejections())->keyBy('label');

        $this->assertSame(1, $rejections['Erstellt / Versendet']['count']);
        $this->assertSame(1, $rejections['1. Gespräch']['count']);
        $this->assertSame(0, $rejections['3. Gespräch']['count']);
        $this->assertCount(1, $rejections['1. Gespräch']['applications']);
    }

    /**
     * Gezählt wird nach dem ersten Eintrag im Verlauf; leere Monate bleiben
     * stehen, damit Pausen sichtbar werden.
     */
    public function test_months_run_without_gaps_up_to_now(): void
    {
        $this->profile();

        $months = collect($this->statistics()->months())->pluck('count', 'month')->all();

        $this->assertSame(['2026-07' => 1, '2026-08' => 0, '2026-09' => 2], $months);
    }

    public function test_the_page_shows_only_my_applications(): void
    {
        $this->profile();
        Application::factory()->create();

        $this->actingAs($this->user)
            ->get(route('statistics.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('summary.total', 3)
                ->where('summary.rejected', 2)
                ->where('summary.interviewed', 1));
    }

    public function test_without_applications_the_page_still_opens(): void
    {
        $this->actingAs($this->user)
            ->get(route('statistics.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('summary.total', 0)->where('months', []));
    }
}
