<?php

namespace App\Ai\Tasks;

use App\Ai\AiClient;
use App\Ai\AiException;
use App\Ai\Contracts\AiTaskHandler;
use App\Ai\Schemas\ExtractFieldsSchema;
use App\Models\AiTask;
use App\Models\GeneratorSession;
use App\Support\JobPosting\PostingText;
use App\Support\Translations;

/**
 * Liest Unternehmen, Position, Ansprechperson, Ort und Anschrift aus der
 * Anzeige.
 *
 * Geschrieben wird nur in leere Felder: Was der Nutzer selbst eingetragen hat,
 * weiß er besser als das Modell — und eine Korrektur, die beim nächsten Klick
 * wieder verschwindet, wäre ärgerlicher als ein leeres Feld.
 */
class ExtractFieldsHandler implements AiTaskHandler
{
    private const FIELDS = ['company', 'position', 'contact_person', 'city', 'company_address'];

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
            $ai->prompts()->render('extract_fields', [
                'posting' => PostingText::limit((string) $session->job_posting),
            ]),
            ExtractFieldsSchema::make(),
            1024,
        );

        $found = [];
        $filled = [];

        foreach (self::FIELDS as $field) {
            $found[$field] = Translations::text($result[$field] ?? null);

            if ($found[$field] !== '' && trim((string) $session->getAttribute($field)) === '') {
                $session->setAttribute($field, $found[$field]);
                $filled[] = $field;
            }
        }

        $session->save();

        return [
            // Was jetzt in der Sitzung steht — das Frontend übernimmt es
            // unverändert, damit beide Seiten dasselbe zeigen.
            'fields' => $session->only(self::FIELDS),
            'found' => $found,
            'filled' => $filled,
        ];
    }
}
