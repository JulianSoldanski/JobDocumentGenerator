<?php

namespace Tests\Feature;

use App\Enums\ApplicationStage;
use App\Models\Application;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Der Tracker: Stufen werden angehängt, nie überschrieben (Prinzip 4).
 */
class ApplicationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    private function application(ApplicationStage $stage = ApplicationStage::Created, string $since = '-10 days'): Application
    {
        $application = Application::factory()->for($this->user)->create();
        $application->moveTo($stage, now()->modify($since));

        return $application->fresh();
    }

    public function test_the_list_shows_only_my_applications_running_ones_first(): void
    {
        $rejected = $this->application(ApplicationStage::Rejected, '-1 day');
        $running = $this->application(ApplicationStage::Sent, '-5 days');
        Application::factory()->create(['company' => 'Fremd GmbH']);

        $this->actingAs($this->user)
            ->get(route('applications.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('applications', 2)
                ->where('applications.0.id', $running->id)
                ->where('applications.1.id', $rejected->id));
    }

    public function test_an_application_from_outside_the_tool_starts_at_its_own_stage(): void
    {
        $this->actingAs($this->user)->post(route('applications.store'), [
            'company' => 'Musterfirma',
            'position' => 'Product Owner',
            'stage' => 'sent',
            'date' => now()->subDays(3)->toDateString(),
        ])->assertRedirect();

        $application = $this->user->applications()->sole();
        $this->assertSame(ApplicationStage::Sent, $application->current_stage);
        $this->assertSame(now()->subDays(3)->toDateString(), $application->applied_on->toDateString());
        $this->assertSame('musterfirma', $application->company_key);
    }

    public function test_the_same_position_at_the_same_company_is_not_filed_twice(): void
    {
        $existing = $this->application();

        $this->actingAs($this->user)->post(route('applications.store'), [
            'company' => strtoupper($existing->company),
            'position' => $existing->position,
            'stage' => 'created',
            'date' => now()->toDateString(),
        ])->assertSessionHasErrors('position');

        $this->assertSame(1, $this->user->applications()->count());
    }

    public function test_a_stage_change_is_appended_and_sending_sets_the_date(): void
    {
        $application = $this->application();

        $this->actingAs($this->user)->post(route('applications.stage', $application), [
            'stage' => 'sent',
            'date' => now()->toDateString(),
        ])->assertSessionHasNoErrors();

        $application->refresh();
        $this->assertSame(ApplicationStage::Sent, $application->current_stage);
        $this->assertSame(now()->toDateString(), $application->applied_on->toDateString());
        $this->assertSame(['created', 'sent'], $application->stageEvents->pluck('stage')->map->value->all());
    }

    /**
     * Nachtragen ja, aber nie vor den letzten Eintrag — sonst widerspräche
     * sich der Verlauf.
     */
    public function test_a_change_cannot_be_dated_before_the_last_entry(): void
    {
        $application = $this->application(ApplicationStage::Sent, '-2 days');

        $this->actingAs($this->user)->post(route('applications.stage', $application), [
            'stage' => 'interview_1',
            'date' => now()->subDays(5)->toDateString(),
        ])->assertSessionHasErrors('date');

        $this->actingAs($this->user)->post(route('applications.stage', $application), [
            'stage' => 'sent',
            'date' => now()->toDateString(),
        ])->assertSessionHasErrors('stage');

        $this->assertCount(1, $application->fresh()->stageEvents, 'Abgelehnte Wechsel hinterlassen nichts.');
    }

    /**
     * Eine Absage ist ein Abbruch: Man sieht, wie weit es ging, und
     * Reaktivieren stellt die Stufe von vorher wieder her.
     */
    public function test_a_rejection_keeps_the_way_and_can_be_undone(): void
    {
        $application = $this->application(ApplicationStage::Sent, '-9 days');
        $application->moveTo(ApplicationStage::Interview1, now()->subDays(5));

        $this->actingAs($this->user)->post(route('applications.stage', $application), [
            'stage' => 'rejected',
            'date' => now()->toDateString(),
        ])->assertSessionHasNoErrors();

        $application->refresh();
        $this->assertTrue($application->isRejected());
        $this->assertSame(ApplicationStage::Interview1, $application->highestStageReached());

        $this->actingAs($this->user)->post(route('applications.reactivate', $application))->assertRedirect();

        $this->assertSame(ApplicationStage::Interview1, $application->fresh()->current_stage);
        // Versendet, 1. Gespräch, Absage, 1. Gespräch — auch die Absage bleibt im Verlauf.
        $this->assertCount(4, $application->fresh()->stageEvents);
    }

    public function test_details_and_feedback_are_saved(): void
    {
        $application = $this->application();

        $this->actingAs($this->user)->patch(route('applications.update', $application), [
            'company' => 'Neuer Name GmbH',
            'position' => $application->position,
            'applied_on' => '2026-09-01',
            'feedback' => 'Zu wenig Erfahrung mit Embedded Linux.',
        ])->assertSessionHasNoErrors();

        $application->refresh();
        $this->assertSame('neuer name gmbh', $application->company_key, 'Der Vergleichsschlüssel folgt dem Namen.');
        $this->assertSame('Zu wenig Erfahrung mit Embedded Linux.', $application->feedback);
    }

    public function test_deleting_removes_the_application_with_its_history(): void
    {
        $application = $this->application(ApplicationStage::Sent);

        $this->actingAs($this->user)
            ->delete(route('applications.destroy', $application))
            ->assertRedirect(route('applications.index'));

        $this->assertModelMissing($application);
        $this->assertDatabaseMissing('stage_events', ['application_id' => $application->id]);
    }

    public function test_applications_of_other_users_stay_private(): void
    {
        $application = Application::factory()->create();

        $this->actingAs($this->user)->get(route('applications.show', $application))->assertForbidden();
        $this->actingAs($this->user)->post(route('applications.stage', $application), ['stage' => 'sent', 'date' => now()->toDateString()])->assertForbidden();
        $this->actingAs($this->user)->delete(route('applications.destroy', $application))->assertForbidden();

        $this->assertModelExists($application);
    }
}
