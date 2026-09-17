<?php

namespace App\Ai;

use App\Enums\AiTaskStatus;
use App\Enums\AiTaskType;
use App\Jobs\RunAiTask;
use App\Models\AiTask;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * KI-Aufrufe laufen im Hintergrund: Der Browser wartet nicht auf die Antwort,
 * sondern fragt den Stand ab. Teilergebnisse erscheinen, sobald sie da sind.
 */
class AiTasks
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function dispatch(User $user, AiTaskType $type, array $input = [], ?Model $subject = null): AiTask
    {
        $task = new AiTask([
            'type' => $type,
            'input' => $input,
        ]);

        $task->user()->associate($user);
        $task->status = AiTaskStatus::Queued;

        if ($subject instanceof Model) {
            $task->subject()->associate($subject);
        }

        $task->save();

        RunAiTask::dispatch($task->id);

        return $task;
    }
}
