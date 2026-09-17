<?php

namespace Tests\Feature;

use App\Enums\ApplicationStage;
use App\Models\Application;
use App\Models\StageEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

/**
 * Prinzip 4: Der Status wird nicht überschrieben. Jeder Wechsel ist ein
 * Ereignis, das nur angehängt wird.
 */
class StageEventAppendOnlyTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_stage_event_cannot_be_updated(): void
    {
        $event = $this->event();

        $this->expectException(LogicException::class);

        $event->update(['stage' => ApplicationStage::Sent]);
    }

    public function test_a_stage_event_cannot_be_deleted_on_its_own(): void
    {
        $event = $this->event();

        $this->expectException(LogicException::class);

        $event->delete();
    }

    public function test_deleting_the_application_removes_its_history(): void
    {
        $event = $this->event();
        $application = $event->application;

        $application->delete();

        $this->assertDatabaseMissing('stage_events', ['id' => $event->id]);
    }

    private function event(): StageEvent
    {
        $application = Application::factory()->create();

        return $application->stageEvents()->create([
            'stage' => ApplicationStage::Created,
            'occurred_at' => now(),
        ]);
    }
}
