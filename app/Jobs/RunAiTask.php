<?php

namespace App\Jobs;

use App\Ai\TaskRegistry;
use App\Models\AiTask;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Führt einen KI-Aufruf aus. Ein Task, ein Job — damit die drei Aufrufe des
 * Generierens unabhängig voneinander fertig werden können.
 */
class RunAiTask implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 180;

    public function __construct(public readonly string $taskId) {}

    public function handle(TaskRegistry $registry): void
    {
        $task = AiTask::find($this->taskId);

        if ($task === null) {
            return;
        }

        $task->markRunning();

        try {
            $task->markSucceeded($registry->handlerFor($task->type)->handle($task));
        } catch (Throwable $e) {
            Log::warning('KI-Aufgabe fehlgeschlagen', [
                'task' => $task->id,
                'type' => $task->type->value,
                'error' => $e->getMessage(),
            ]);

            $task->markFailed($e->getMessage());
        }
    }

    /**
     * Auch ein harter Abbruch (Timeout, Speicher) darf keine Aufgabe für immer
     * auf "läuft" stehen lassen.
     */
    public function failed(?Throwable $e): void
    {
        AiTask::find($this->taskId)?->markFailed(
            $e?->getMessage() ?? 'Die Aufgabe wurde abgebrochen.'
        );
    }
}
