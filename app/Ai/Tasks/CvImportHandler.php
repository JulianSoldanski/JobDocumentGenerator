<?php

namespace App\Ai\Tasks;

use App\Ai\AiClient;
use App\Ai\Contracts\AiTaskHandler;
use App\Ai\Schemas\CvImportSchema;
use App\Enums\Language;
use App\Models\AiTask;
use App\Support\Translations;

/**
 * Liest einen vorhandenen Lebenslauf in die Profilstruktur.
 *
 * Das Ergebnis ist ein Vorschlag: Der Nutzer prüft ihn und entscheidet, was
 * übernommen wird. Die Texte landen in der Sprache, in der sie im PDF stehen —
 * die andere Sprache bleibt leer und wird im Editor als fehlend markiert.
 */
class CvImportHandler implements AiTaskHandler
{
    public function __construct(private readonly AiClient $ai) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(AiTask $task): array
    {
        // Jeder Aufruf läuft mit dem Zugang des Nutzers, dem er gehört.
        $ai = $this->ai->using($task->user);

        $text = Translations::text($task->input['text'] ?? null);

        $result = $ai->structured(
            $ai->prompts()->render('cv_import', [
                'text' => mb_substr($text, 0, 20000),
            ]),
            CvImportSchema::make(),
            8192,
        );

        $language = Language::tryFrom(Translations::text($result['language'] ?? null)) ?? Language::German;

        return [
            'language' => $language->value,
            'contact' => $this->contact($result['contact'] ?? []),
            'experience' => $this->entries($result['experience'] ?? [], 'title'),
            'education' => $this->entries($result['education'] ?? [], 'degree'),
            'hard_skills' => Translations::lines($result['hard_skills'] ?? []),
            'soft_skills' => Translations::lines($result['soft_skills'] ?? []),
            'languages' => $this->languages($result['languages'] ?? []),
            'projects' => $this->projects($result['projects'] ?? []),
        ];
    }

    /**
     * @param  mixed  $raw
     * @return array<string, string>
     */
    private function contact($raw): array
    {
        $raw = is_array($raw) ? $raw : [];

        $contact = [];
        foreach (['full_name', 'street', 'postal_code', 'city', 'phone', 'email'] as $field) {
            $contact[$field] = Translations::text($raw[$field] ?? null);
        }

        return $contact;
    }

    /**
     * @param  mixed  $raw
     * @return array<int, array<string, mixed>>
     */
    private function entries($raw, string $headlineField): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $entries = [];
        $listField = $headlineField === 'title' ? 'bullets' : 'details';

        foreach ($raw as $item) {
            if (! is_array($item)) {
                continue;
            }

            $headline = Translations::text($item[$headlineField] ?? null);
            if ($headline === '') {
                continue;
            }

            $entries[] = [
                $headlineField => $headline,
                'organization' => Translations::text($item['organization'] ?? null),
                'location' => Translations::text($item['location'] ?? null),
                'start_month' => $this->month($item['start_month'] ?? null),
                'end_month' => $this->month($item['end_month'] ?? null),
                'is_current' => filter_var($item['is_current'] ?? false, FILTER_VALIDATE_BOOL),
                $listField => Translations::lines($item[$listField] ?? []),
            ];
        }

        return $entries;
    }

    /**
     * @param  mixed  $raw
     * @return array<int, array<string, string>>
     */
    private function languages($raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $languages = [];
        foreach ($raw as $item) {
            $name = is_array($item) ? Translations::text($item['name'] ?? null) : Translations::text($item);
            if ($name === '') {
                continue;
            }

            $languages[] = [
                'name' => $name,
                'level' => is_array($item) ? Translations::text($item['level'] ?? null) : '',
            ];
        }

        return $languages;
    }

    /**
     * @param  mixed  $raw
     * @return array<int, array<string, mixed>>
     */
    private function projects($raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $projects = [];
        foreach ($raw as $item) {
            if (! is_array($item)) {
                continue;
            }

            $title = Translations::text($item['title'] ?? null);
            if ($title === '') {
                continue;
            }

            $projects[] = [
                'title' => $title,
                'summary' => Translations::text($item['summary'] ?? null),
                'tags' => Translations::lines($item['tags'] ?? []),
            ];
        }

        return $projects;
    }

    /**
     * Nur ein vollständiges JJJJ-MM ist brauchbar; alles andere bleibt leer,
     * statt ein falsches Datum zu behaupten.
     */
    private function month(mixed $value): ?string
    {
        $value = Translations::text($value);

        return preg_match('/^\d{4}-\d{2}$/', $value) === 1 ? $value : null;
    }
}
