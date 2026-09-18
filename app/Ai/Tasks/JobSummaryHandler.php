<?php

namespace App\Ai\Tasks;

use App\Ai\AiClient;
use App\Ai\AiException;
use App\Ai\Contracts\AiTaskHandler;
use App\Ai\Schemas\JobSummarySchema;
use App\Models\AiTask;
use App\Models\GeneratorSession;
use App\Support\JobPosting\PostingText;
use App\Support\Translations;

/**
 * Die Stellen-Übersicht: Was macht das Unternehmen, wen sucht es, welche
 * Technologien.
 *
 * Sie bleibt links stehen, während rechts am Dokument gearbeitet wird —
 * deshalb gehört sie an die Sitzung und nicht nur in das Ergebnis der Aufgabe.
 */
class JobSummaryHandler implements AiTaskHandler
{
    public function __construct(private readonly AiClient $ai) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(AiTask $task): array
    {
        $session = $task->subject;

        if (! $session instanceof GeneratorSession) {
            throw new AiException('Zu dieser Aufgabe gehört keine Sitzung.');
        }

        if (! $session->hasPosting()) {
            throw new AiException('Es liegt noch keine Stellenanzeige vor.');
        }

        $ai = $this->ai->using($task->user);

        $result = $ai->structured(
            $ai->prompts()->render('job_summary', [
                'posting' => PostingText::limit((string) $session->job_posting),
            ]),
            JobSummarySchema::make(),
            2048,
        );

        $summary = [
            'company' => Translations::text($result['company'] ?? null),
            'role' => Translations::text($result['role'] ?? null),
            'technologies' => Translations::lines($result['technologies'] ?? []),
        ];

        $session->forceFill(['summary' => $summary])->save();

        return ['summary' => $summary];
    }
}
