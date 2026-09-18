<?php

namespace App\Ai\Tasks;

use App\Ai\AiClient;
use App\Ai\AiException;
use App\Ai\Contracts\AiTaskHandler;
use App\Ai\Schemas\CoverLetterSchema;
use App\Documents\CvAssembler;
use App\Enums\DocumentType;
use App\Models\AiTask;
use App\Models\GeneratorSession;
use App\Support\JobPosting\PostingText;
use App\Support\Translations;

/**
 * Das Anschreiben: Fließtext für genau eine Stelle, erzeugt statt
 * zusammengesetzt (Prinzip 3).
 *
 * Die Stimme kommt aus den Stilregeln des Nutzers. In den Prompt geht die
 * Regelliste, nie das Beispiel-Anschreiben — sonst übernimmt das Modell
 * dessen Inhalte statt nur dessen Ton.
 */
class CoverLetterHandler implements AiTaskHandler
{
    private const NO_RULES = 'Keine eigenen Regeln hinterlegt. Schreibe sachlich, direkt und ohne Floskeln.';

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

        $user = $task->user;
        $assembler = new CvAssembler($user, $session->language);

        // Ohne Profil müsste das Modell Qualifikationen erfinden.
        if (! $assembler->hasContent()) {
            throw new AiException('Dein Profil ist noch leer — ohne es gäbe es nichts, worauf sich das Anschreiben stützen kann.');
        }

        $candidates = $assembler->candidates();
        $rules = Translations::lines($user->style()->rules ?? []);
        $ai = $this->ai->using($user);

        $result = $ai->structured(
            $ai->prompts()->render('cover_letter', [
                'language' => $session->language->label(),
                'company' => $session->company ?: '(nicht angegeben)',
                'position' => $session->position ?: '(nicht angegeben)',
                'contact_person' => $session->contact_person ?: '(keine genannt)',
                'posting' => PostingText::limit((string) $session->job_posting),
                'notes' => trim((string) $session->notes) ?: '–',
                'experience' => self::json($assembler->experienceOutline()),
                'education' => self::json($assembler->educationOutline()),
                'projects' => self::json($candidates['projects']),
                'skills' => implode(', ', array_column(
                    array_merge($candidates['hard_skills'], $candidates['soft_skills']),
                    'name'
                )) ?: '–',
                'style_rules' => $rules === []
                    ? self::NO_RULES
                    : implode("\n", array_map(fn (string $rule): string => "- {$rule}", $rules)),
            ]),
            CoverLetterSchema::make(),
            3072,
        );

        $paragraphs = Translations::lines($result['paragraphs'] ?? []);

        if ($paragraphs === []) {
            throw new AiException('Das Modell hat kein Anschreiben geschrieben.');
        }

        $document = $session->storeDocument(DocumentType::Letter, [
            'sender' => $assembler->contact(),
            'recipient' => [
                'company' => $session->company,
                'contact_person' => $session->contact_person,
                'address' => (string) $session->company_address,
            ],
            'place' => $user->contact()->city,
            'date' => now()->toDateString(),
            'subject' => Translations::text($result['subject'] ?? null),
            'salutation' => Translations::text($result['salutation'] ?? null),
            'paragraphs' => $paragraphs,
            'closing' => Translations::text($result['closing'] ?? null),
        ]);

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
