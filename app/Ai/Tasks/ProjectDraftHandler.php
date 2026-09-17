<?php

namespace App\Ai\Tasks;

use App\Ai\AiClient;
use App\Ai\Contracts\AiTaskHandler;
use App\Ai\Schemas\ProjectDraftSchema;
use App\Models\AiTask;
use App\Models\Project;
use App\Support\Translations;

/**
 * Entwirft aus der Kurzfassung eines Projekts die ausführliche Fassung für die
 * Projektliste — in beiden Sprachen, aber nur als Vorschlag.
 */
class ProjectDraftHandler implements AiTaskHandler
{
    public function __construct(private readonly AiClient $ai) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(AiTask $task): array
    {
        // Jeder Aufruf läuft mit dem Zugang des Nutzers, dem er gehört.
        $ai = $this->ai->using($task->user);

        $input = $task->input;
        $subject = $task->subject;

        $existing = $subject instanceof Project
            ? [
                'client' => $subject->client,
                'period' => $subject->period,
                'team_size' => $subject->team_size,
                'technologies' => $subject->technologies,
            ]
            : [];

        $result = $ai->structured(
            $ai->prompts()->render('project_draft', [
                'title' => Translations::text($input['title'] ?? null),
                'summary' => Translations::text($input['summary'] ?? null),
                'tags' => implode(', ', Translations::lines($input['tags'] ?? [])) ?: '–',
                'grade' => Translations::text($input['grade'] ?? null) ?: '–',
                'existing' => json_encode($existing, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?: '{}',
            ]),
            ProjectDraftSchema::make(),
            3072,
        );

        return [
            'client' => Translations::text($result['client'] ?? null),
            'period' => Translations::text($result['period'] ?? null),
            'team_size' => Translations::text($result['team_size'] ?? null),
            'technologies' => Translations::lines($result['technologies'] ?? []),
            'translations' => Translations::normalize(
                [
                    'de' => $result['de'] ?? [],
                    'en' => $result['en'] ?? [],
                ],
                Project::TEXT_FIELDS,
                Project::LIST_FIELDS,
            ),
        ];
    }
}
