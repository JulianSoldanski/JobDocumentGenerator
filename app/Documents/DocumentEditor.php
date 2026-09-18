<?php

namespace App\Documents;

use App\Enums\DocumentType;
use App\Support\Translations;

/**
 * Arbeitet die Änderungen aus dem Editor in den gespeicherten Inhalt ein.
 *
 * Bearbeitet wird das Dokument, nicht das Profil: Eine Änderung für diese
 * Stelle soll nicht in jedem künftigen Lebenslauf auftauchen.
 *
 * Editierbar ist, was die Produktbeschreibung nennt — Statement (und ob es
 * erscheint), Bullet Points, Ausbildungsdetails, Projektauswahl, Skill-Zeilen,
 * Brieftext.
 * Titel, Firmen und Daten bleiben, wie das Profil sie liefert; Einträge werden
 * über ihre ID zugeordnet, nie über die Position im Formular.
 */
class DocumentEditor
{
    /**
     * @param  array<string, mixed>  $content
     * @param  array<string, mixed>  $edits
     * @return array<string, mixed>
     */
    public static function apply(DocumentType $type, array $content, array $edits): array
    {
        $content = match ($type) {
            DocumentType::Cv => self::cv($content, $edits),
            DocumentType::Letter => self::letter($content, $edits),
            DocumentType::ProjectList => $content,
        };

        // Merkt sich, dass Hand angelegt wurde — ein erneutes Generieren würde
        // das überschreiben und soll vorher fragen.
        $content['edited'] = true;

        return $content;
    }

    /**
     * @param  array<string, mixed>  $content
     * @param  array<string, mixed>  $edits
     * @return array<string, mixed>
     */
    private static function cv(array $content, array $edits): array
    {
        if (array_key_exists('statement', $edits)) {
            $content['statement'] = Translations::text($edits['statement']);
        }

        // Ausgeblendet, nicht gelöscht: Der Text bleibt für später.
        if (array_key_exists('statement_included', $edits)) {
            $content['statement_included'] = (bool) $edits['statement_included'];
        }

        $content['experience'] = self::replaceLists($content['experience'] ?? [], $edits['experience'] ?? null, 'bullets');
        $content['experience'] = self::markSubtle($content['experience'], $edits['experience'] ?? null);
        $content['education'] = self::replaceLists($content['education'] ?? [], $edits['education'] ?? null, 'details');

        if (is_array($edits['projects'] ?? null)) {
            $content['projects'] = self::reorderProjects($content['projects'] ?? [], $edits['projects']);
        }

        foreach (['hard', 'soft'] as $category) {
            if (is_array($edits['skills'][$category] ?? null)) {
                $content['skills'][$category] = array_map(
                    fn (string $name): array => ['name' => $name],
                    Translations::lines($edits['skills'][$category]),
                );
            }
        }

        if (is_array($edits['skills']['languages'] ?? null)) {
            $content['skills']['languages'] = array_values(array_filter(array_map(
                fn ($item): array => [
                    'name' => Translations::text(is_array($item) ? ($item['name'] ?? null) : null),
                    'level' => Translations::text(is_array($item) ? ($item['level'] ?? null) : null),
                ],
                $edits['skills']['languages'],
            ), fn (array $item): bool => $item['name'] !== ''));
        }

        return $content;
    }

    /**
     * @param  array<string, mixed>  $content
     * @param  array<string, mixed>  $edits
     * @return array<string, mixed>
     */
    private static function letter(array $content, array $edits): array
    {
        foreach (['subject', 'salutation', 'closing'] as $field) {
            if (array_key_exists($field, $edits)) {
                $content[$field] = Translations::text($edits[$field]);
            }
        }

        if (is_array($edits['paragraphs'] ?? null)) {
            $content['paragraphs'] = Translations::lines($edits['paragraphs']);
        }

        return $content;
    }

    /**
     * Ob eine Station leiser erscheint, entscheidet jeder Lebenslauf selbst —
     * das Profil gibt nur den Anfang vor.
     *
     * @param  array<int, mixed>  $entries
     * @return array<int, mixed>
     */
    private static function markSubtle(array $entries, mixed $edits): array
    {
        if (! is_array($edits)) {
            return $entries;
        }

        $subtle = [];

        foreach ($edits as $edit) {
            if (is_array($edit) && isset($edit['id']) && array_key_exists('subtle', $edit)) {
                $subtle[(string) $edit['id']] = (bool) $edit['subtle'];
            }
        }

        return array_map(function ($entry) use ($subtle) {
            $id = is_array($entry) ? (string) ($entry['id'] ?? '') : '';

            if ($id !== '' && array_key_exists($id, $subtle)) {
                $entry['subtle'] = $subtle[$id];
            }

            return $entry;
        }, $entries);
    }

    /**
     * Ersetzt die Liste eines Eintrags — gefunden über seine ID.
     *
     * @param  mixed  $entries
     * @param  mixed  $edits
     * @return array<int, mixed>
     */
    private static function replaceLists($entries, $edits, string $field): array
    {
        $entries = is_array($entries) ? array_values($entries) : [];

        if (! is_array($edits)) {
            return $entries;
        }

        $byId = [];

        foreach ($edits as $edit) {
            if (is_array($edit) && isset($edit['id'])) {
                $byId[(string) $edit['id']] = Translations::lines($edit[$field] ?? []);
            }
        }

        return array_map(function ($entry) use ($byId, $field) {
            $id = is_array($entry) ? (string) ($entry['id'] ?? '') : '';

            if ($id !== '' && array_key_exists($id, $byId)) {
                $entry[$field] = $byId[$id];
            }

            return $entry;
        }, $entries);
    }

    /**
     * Reihenfolge und Schalter aus dem Editor. Unbekannte IDs zählen nicht;
     * ein Projekt, das im Formular fehlt, bleibt ausgeschaltet am Ende
     * erhalten statt zu verschwinden.
     *
     * @param  mixed  $projects
     * @param  array<int, mixed>  $edits
     * @return array<int, mixed>
     */
    private static function reorderProjects($projects, array $edits): array
    {
        $stored = [];

        foreach (is_array($projects) ? $projects : [] as $project) {
            if (is_array($project) && isset($project['id'])) {
                $stored[(string) $project['id']] = $project;
            }
        }

        $ordered = [];

        foreach ($edits as $edit) {
            $id = is_array($edit) ? (string) ($edit['id'] ?? '') : '';

            if (isset($stored[$id]) && ! isset($ordered[$id])) {
                $ordered[$id] = [...$stored[$id], 'included' => (bool) ($edit['included'] ?? false)];
            }
        }

        foreach ($stored as $id => $project) {
            $ordered[$id] ??= [...$project, 'included' => false];
        }

        return array_values($ordered);
    }
}
