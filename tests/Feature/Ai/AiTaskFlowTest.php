<?php

namespace Tests\Feature\Ai;

use App\Ai\TaskRegistry;
use App\Enums\AiTaskStatus;
use App\Enums\AiTaskType;
use App\Jobs\RunAiTask;
use App\Models\AiTask;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\StructuredResponseFake;
use Tests\TestCase;

/**
 * KI-Aufrufe laufen im Hintergrund: Der Aufruf legt eine Aufgabe an, der Job
 * arbeitet sie ab, das Frontend fragt den Stand ab.
 */
class AiTaskFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_style_analysis_is_queued_and_not_run_in_the_request(): void
    {
        Queue::fake();
        $user = $this->withAiKey(User::factory()->create());

        $response = $this->actingAs($user)->postJson(route('profile.style.analyze'), [
            'example' => str_repeat('Ein Beispieltext, der lang genug ist. ', 10),
        ]);

        $response->assertOk()->assertJsonStructure(['task_id']);
        Queue::assertPushed(RunAiTask::class);

        $task = $user->aiTasks()->sole();
        $this->assertSame(AiTaskType::StyleAnalysis, $task->type);
        $this->assertSame(AiTaskStatus::Queued, $task->status);
    }

    public function test_a_short_example_is_rejected_before_it_costs_anything(): void
    {
        Queue::fake();
        $user = $this->withAiKey(User::factory()->create());

        $this->actingAs($user)
            ->postJson(route('profile.style.analyze'), ['example' => 'Zu kurz.'])
            ->assertStatus(422);

        Queue::assertNothingPushed();
    }

    public function test_running_the_job_stores_the_rules(): void
    {
        Prism::fake([
            StructuredResponseFake::make()->withStructured([
                'rules' => ['Kurze Hauptsätze.', '   ', 'Keine Floskeln.'],
            ]),
        ]);

        $user = $this->withAiKey(User::factory()->create());
        $task = $user->aiTasks()->create([
            'type' => AiTaskType::StyleAnalysis,
            'input' => ['example' => 'Ein Beispiel.'],
        ]);

        (new RunAiTask($task->id))->handle(app(TaskRegistry::class));

        $task->refresh();
        $this->assertSame(AiTaskStatus::Succeeded, $task->status);
        $this->assertSame(['Kurze Hauptsätze.', 'Keine Floskeln.'], $task->result['rules']);
    }

    public function test_a_failing_call_marks_the_task_instead_of_blowing_up(): void
    {
        Prism::fake([StructuredResponseFake::make()->withStructured([])]);

        $user = $this->withAiKey(User::factory()->create());
        $task = $user->aiTasks()->create([
            'type' => AiTaskType::StyleAnalysis,
            'input' => ['example' => 'Ein Beispiel.'],
        ]);

        (new RunAiTask($task->id))->handle(app(TaskRegistry::class));

        $task->refresh();
        $this->assertSame(AiTaskStatus::Failed, $task->status);
        $this->assertNotNull($task->error);
    }

    public function test_the_status_endpoint_reports_the_result(): void
    {
        $user = $this->withAiKey(User::factory()->create());
        $task = $user->aiTasks()->create([
            'type' => AiTaskType::StyleAnalysis,
            'input' => [],
        ]);
        $task->markSucceeded(['rules' => ['Kurze Sätze.']]);

        $this->actingAs($user)
            ->getJson(route('ai-tasks.show', $task))
            ->assertOk()
            ->assertJson([
                'status' => 'succeeded',
                'result' => ['rules' => ['Kurze Sätze.']],
            ]);
    }

    public function test_tasks_of_other_users_stay_private(): void
    {
        $owner = $this->withAiKey(User::factory()->create());
        $stranger = $this->withAiKey(User::factory()->create());
        $task = $owner->aiTasks()->create(['type' => AiTaskType::StyleAnalysis, 'input' => []]);

        $this->actingAs($stranger)
            ->getJson(route('ai-tasks.show', $task))
            ->assertForbidden();
    }

    public function test_a_project_draft_carries_the_stored_facts_into_the_prompt(): void
    {
        $fake = Prism::fake([
            StructuredResponseFake::make()->withStructured([
                'client' => 'MusterTech GmbH',
                'period' => '03/2024 – 07/2024',
                'team_size' => '4 Personen',
                'technologies' => ['React'],
                'de' => [
                    'title' => 'Dashboard',
                    'summary' => 'Kurz.',
                    'role' => 'Fullstack',
                    'situation' => 'Verstreute Auswertungen.',
                    'contributions' => ['Datenmodell entworfen'],
                    'result' => 'Basis der Reviews.',
                ],
                'en' => [
                    'title' => 'Dashboard',
                    'summary' => 'Short.',
                    'role' => 'Fullstack',
                    'situation' => 'Scattered reporting.',
                    'contributions' => ['Designed the data model'],
                    'result' => 'Basis for reviews.',
                ],
            ]),
        ]);

        $user = $this->withAiKey(User::factory()->create());
        $project = Project::factory()->for($user)->create(['client' => 'MusterTech GmbH']);

        $task = $user->aiTasks()->create([
            'type' => AiTaskType::ProjectDraft,
            'input' => ['title' => 'Dashboard', 'summary' => 'Kurz.', 'tags' => ['react'], 'grade' => ''],
        ]);
        $task->subject()->associate($project)->save();

        (new RunAiTask($task->id))->handle(app(TaskRegistry::class));

        $result = $task->refresh()->result;
        $this->assertSame('MusterTech GmbH', $result['client']);
        $this->assertSame(['Datenmodell entworfen'], $result['translations']['de']['contributions']);
        $this->assertSame('Designed the data model', $result['translations']['en']['contributions'][0]);

        $fake->assertRequest(function (array $requests) use ($project): void {
            $this->assertStringContainsString($project->client, $requests[0]->prompt());
        });
    }

    public function test_the_draft_is_not_saved_on_its_own(): void
    {
        Prism::fake([
            StructuredResponseFake::make()->withStructured([
                'client' => 'Erfunden GmbH',
                'period' => '', 'team_size' => '', 'technologies' => [],
                'de' => ['title' => 'Neu', 'summary' => 'Neu.', 'role' => '', 'situation' => '', 'contributions' => [], 'result' => ''],
                'en' => ['title' => 'New', 'summary' => 'New.', 'role' => '', 'situation' => '', 'contributions' => [], 'result' => ''],
            ]),
        ]);

        $user = $this->withAiKey(User::factory()->create());
        $project = Project::factory()->for($user)->create(['client' => '']);
        $task = $user->aiTasks()->create([
            'type' => AiTaskType::ProjectDraft,
            'input' => ['title' => 'Dashboard', 'summary' => 'Kurz.'],
        ]);
        $task->subject()->associate($project)->save();

        (new RunAiTask($task->id))->handle(app(TaskRegistry::class));

        $this->assertSame('', $project->fresh()->client, 'Ein Entwurf ist ein Vorschlag, keine Änderung.');
    }

    public function test_the_rate_limit_protects_the_wallet(): void
    {
        Queue::fake();
        config(['cvcreater.ai.rate_limit' => 2]);
        $user = $this->withAiKey(User::factory()->create());
        $example = str_repeat('Ein Beispieltext, der lang genug ist. ', 10);

        for ($i = 0; $i < 2; $i++) {
            $this->actingAs($user)
                ->postJson(route('profile.style.analyze'), ['example' => $example])
                ->assertOk();
        }

        $this->actingAs($user)
            ->postJson(route('profile.style.analyze'), ['example' => $example])
            ->assertStatus(429);
    }

    public function test_a_lost_job_does_not_leave_a_task_running_forever(): void
    {
        $user = $this->withAiKey(User::factory()->create());
        $task = $user->aiTasks()->create(['type' => AiTaskType::StyleAnalysis, 'input' => []]);
        $task->markRunning();

        (new RunAiTask($task->id))->failed(new \RuntimeException('Timeout'));

        $this->assertSame(AiTaskStatus::Failed, $task->refresh()->status);
        $this->assertSame('Timeout', $task->error);
    }

    public function test_an_unknown_task_id_is_simply_ignored(): void
    {
        (new RunAiTask('00000000-0000-0000-0000-000000000000'))->handle(app(TaskRegistry::class));

        $this->assertSame(0, AiTask::count());
    }
}
