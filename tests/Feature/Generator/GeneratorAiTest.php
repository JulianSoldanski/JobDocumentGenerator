<?php

namespace Tests\Feature\Generator;

use App\Ai\TaskRegistry;
use App\Enums\AiTaskType;
use App\Jobs\RunAiTask;
use App\Models\GeneratorSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\StructuredResponseFake;
use Tests\TestCase;

/**
 * Die beiden Aufrufe der linken Hälfte: Felder auslesen und Stellen-Übersicht.
 */
class GeneratorAiTest extends TestCase
{
    use RefreshDatabase;

    private function workspace(User $user, bool $withPosting = true): GeneratorSession
    {
        $factory = GeneratorSession::factory()->for($user);

        return ($withPosting ? $factory->withPosting() : $factory)->create();
    }

    private function runTask(User $user, AiTaskType $type, GeneratorSession $session): void
    {
        $task = $user->aiTasks()->create(['type' => $type, 'input' => []]);
        $task->subject()->associate($session)->save();

        (new RunAiTask($task->id))->handle(app(TaskRegistry::class));
    }

    public function test_extracting_fields_is_queued_and_not_run_in_the_request(): void
    {
        Queue::fake();
        $user = $this->withAiKey(User::factory()->create());
        $session = $this->workspace($user);

        $this->actingAs($user)
            ->postJson(route('generator.fields', $session))
            ->assertOk()
            ->assertJsonStructure(['task_id']);

        Queue::assertPushed(RunAiTask::class);

        $task = $user->aiTasks()->sole();
        $this->assertSame(AiTaskType::ExtractFields, $task->type);
        $this->assertTrue($session->is($task->subject));
    }

    /**
     * Ohne Anzeige gibt es nichts auszulesen — das muss auffallen, bevor ein
     * Aufruf Geld kostet.
     */
    public function test_without_a_posting_nothing_is_dispatched(): void
    {
        Queue::fake();
        $user = $this->withAiKey(User::factory()->create());
        $session = $this->workspace($user, withPosting: false);

        $this->actingAs($user)
            ->postJson(route('generator.fields', $session))
            ->assertStatus(422);

        $this->actingAs($user)
            ->postJson(route('generator.summary', $session))
            ->assertStatus(422);

        Queue::assertNothingPushed();
    }

    public function test_without_an_api_key_the_call_never_starts(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $session = $this->workspace($user);

        $this->actingAs($user)
            ->postJson(route('generator.fields', $session))
            ->assertStatus(422);

        Queue::assertNothingPushed();
    }

    public function test_only_empty_fields_are_filled(): void
    {
        Prism::fake([
            StructuredResponseFake::make()->withStructured([
                'company' => 'MusterTech GmbH',
                'position' => 'Fullstack-Entwicklerin',
                'contact_person' => 'Frau Dr. Meyer',
                'city' => 'Hamburg',
                'company_address' => "Musterstraße 1\n20095 Hamburg",
            ]),
        ]);

        $user = $this->withAiKey(User::factory()->create());
        $session = $this->workspace($user);

        // Was der Nutzer selbst eingetragen hat, weiß er besser als das Modell.
        $session->forceFill(['company' => 'MusterTech Deutschland GmbH'])->save();

        $this->runTask($user, AiTaskType::ExtractFields, $session);

        $session->refresh();
        $this->assertSame('MusterTech Deutschland GmbH', $session->company);
        $this->assertSame('Fullstack-Entwicklerin', $session->position);
        $this->assertSame('Frau Dr. Meyer', $session->contact_person);
        $this->assertSame('Hamburg', $session->city);

        $result = $user->aiTasks()->sole()->result;
        $this->assertSame('MusterTech Deutschland GmbH', $result['fields']['company']);
        $this->assertSame('MusterTech GmbH', $result['found']['company'], 'Was das Modell fand, bleibt sichtbar.');
        $this->assertNotContains('company', $result['filled']);
        $this->assertContains('position', $result['filled']);
    }

    public function test_a_field_the_model_leaves_empty_stays_empty(): void
    {
        Prism::fake([
            StructuredResponseFake::make()->withStructured([
                'company' => 'MusterTech GmbH',
                'position' => '',
                'contact_person' => '   ',
                'city' => 'Hamburg',
                'company_address' => '',
            ]),
        ]);

        $user = $this->withAiKey(User::factory()->create());
        $session = $this->workspace($user);

        $this->runTask($user, AiTaskType::ExtractFields, $session);

        $session->refresh();
        $this->assertSame('', $session->position);
        $this->assertSame('', $session->contact_person);
        $this->assertSame([], array_diff(['company', 'city'], $user->aiTasks()->sole()->result['filled']));
    }

    public function test_the_summary_stays_with_the_session(): void
    {
        Prism::fake([
            StructuredResponseFake::make()->withStructured([
                'company' => 'Baut Software für die Logistikbranche.',
                'role' => 'Sucht eine Fullstack-Entwicklerin für das Kernprodukt.',
                'technologies' => ['Laravel', '  ', 'React'],
            ]),
        ]);

        $user = $this->withAiKey(User::factory()->create());
        $session = $this->workspace($user);

        $this->runTask($user, AiTaskType::JobSummary, $session);

        $summary = $session->refresh()->summary;
        $this->assertSame('Baut Software für die Logistikbranche.', $summary['company']);
        $this->assertSame(['Laravel', 'React'], $summary['technologies'], 'Leere Einträge fallen weg.');
    }

    public function test_a_task_without_a_posting_fails_instead_of_asking_the_model(): void
    {
        $fake = Prism::fake([]);

        $user = $this->withAiKey(User::factory()->create());
        $session = $this->workspace($user, withPosting: false);

        $this->runTask($user, AiTaskType::JobSummary, $session);

        $task = $user->aiTasks()->sole();
        $this->assertSame('failed', $task->status->value);
        $fake->assertCallCount(0);
    }

    public function test_a_session_of_another_user_stays_private(): void
    {
        Queue::fake();
        $owner = $this->withAiKey(User::factory()->create());
        $stranger = $this->withAiKey(User::factory()->create());
        $session = $this->workspace($owner);

        $this->actingAs($stranger)
            ->postJson(route('generator.summary', $session))
            ->assertForbidden();

        Queue::assertNothingPushed();
    }
}
