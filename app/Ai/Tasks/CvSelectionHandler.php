<?php

namespace App\Ai\Tasks;

use App\Ai\AiClient;
use App\Ai\AiException;
use App\Ai\Contracts\AiTaskHandler;
use App\Ai\Schemas\CvSelectionSchema;
use App\Ai\SelectionFilter;
use App\Documents\CvAssembler;
use App\Enums\DocumentType;
use App\Models\AiTask;
use App\Models\GeneratorSession;
use App\Support\JobPosting\PostingText;
use App\Support\Translations;

/**
 * Der Lebenslauf für eine Stelle.
 *
 * Die KI schreibt das Profil-Statement und wählt Projekte und Skills aus —
 * sonst nichts. Ihre IDs werden gegen die erlaubte Liste geprüft; kommt eine
 * leere oder unbrauchbare Auswahl zurück, bleibt der Abschnitt vollständig,
 * statt stillschweigend zu verschwinden.
 */
class CvSelectionHandler implements AiTaskHandler
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

        $assembler = new CvAssembler($task->user, $session->language);

        if (! $assembler->hasContent()) {
            throw new AiException('Dein Profil ist noch leer — der Lebenslauf wird daraus zusammengesetzt.');
        }

        $candidates = $assembler->candidates();
        $ai = $this->ai->using($task->user);

        $result = $ai->structured(
            $ai->prompts()->render('cv_selection', [
                'language' => $session->language->label(),
                'company' => $session->company ?: '(nicht angegeben)',
                'position' => $session->position ?: '(nicht angegeben)',
                'posting' => PostingText::limit((string) $session->job_posting),
                'notes' => trim((string) $session->notes) ?: '–',
                'experience' => self::json($assembler->experienceOutline()),
                'education' => self::json($assembler->educationOutline()),
                'projects' => self::json($candidates['projects']),
                'hard_skills' => self::json($candidates['hard_skills']),
                'soft_skills' => self::json($candidates['soft_skills']),
            ]),
            CvSelectionSchema::make(),
            2048,
        );

        $allowed = fn (string $key): array => array_column($candidates[$key], 'id');

        $content = $assembler->assemble(
            Translations::text($result['statement'] ?? null),
            SelectionFilter::filterOrAll($result['projects'] ?? null, $allowed('projects')),
            SelectionFilter::filterOrAll($result['hard_skills'] ?? null, $allowed('hard_skills')),
            SelectionFilter::filterOrAll($result['soft_skills'] ?? null, $allowed('soft_skills')),
        );

        $document = $session->storeDocument(DocumentType::Cv, $content);

        return [
            // Frisch generiert: Handarbeit von vorher ist damit ersetzt.
            'document' => ['id' => $document->id, 'version' => $document->version, 'edited' => false],
        ];
    }

    /**
     * @param  array<int, mixed>  $value
     */
    private static function json(array $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?: '[]';
    }
}
