<?php

namespace App\Ai\Tasks;

use App\Ai\AiClient;
use App\Ai\Contracts\AiTaskHandler;
use App\Ai\Schemas\StyleSchema;
use App\Models\AiTask;
use App\Support\Translations;

/**
 * Destilliert aus einem Beispiel-Anschreiben editierbare Stilregeln.
 *
 * Gespeichert wird hier nichts: Der Nutzer sieht die Regeln zuerst und
 * übernimmt sie erst, wenn sie stimmen.
 */
class StyleAnalysisHandler implements AiTaskHandler
{
    public function __construct(private readonly AiClient $ai) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(AiTask $task): array
    {
        // Jeder Aufruf läuft mit dem Zugang des Nutzers, dem er gehört.
        $ai = $this->ai->using($task->user);

        $example = Translations::text($task->input['example'] ?? null);

        $result = $ai->structured(
            $ai->prompts()->render('style_analysis', [
                'example' => mb_substr($example, 0, 8000),
            ]),
            StyleSchema::make(),
            2048,
        );

        return ['rules' => Translations::lines($result['rules'] ?? [])];
    }
}
