<?php

namespace App\Support;

use App\Enums\Language;

/**
 * Formhilfen für die zweisprachigen Textblöcke eines Eintrags.
 *
 * Jeder Eintrag existiert auf Deutsch und auf Englisch — beides vom Nutzer
 * verfasst. Hier wird nur die Form sichergestellt und der feldweise Rückgriff
 * aufgelöst; übersetzt wird nichts.
 */
class Translations
{
    /**
     * Bringt beliebige Eingaben in die Form {de: {...}, en: {...}}.
     *
     * Fehlende Stücke kommen als leerer String bzw. leere Liste zurück, damit
     * Editor und Renderer "noch nicht ausgefüllt" einheitlich behandeln können.
     *
     * @param  mixed  $raw
     * @param  array<int, string>  $textFields
     * @param  array<int, string>  $listFields
     * @return array<string, array<string, string|array<int, string>>>
     */
    public static function normalize($raw, array $textFields, array $listFields): array
    {
        $raw = is_array($raw) ? $raw : [];
        $out = [];

        foreach (Language::cases() as $language) {
            $block = $raw[$language->value] ?? [];
            $block = is_array($block) ? $block : [];

            $normalized = [];
            foreach ($textFields as $field) {
                $normalized[$field] = self::text($block[$field] ?? null);
            }
            foreach ($listFields as $field) {
                $normalized[$field] = self::lines($block[$field] ?? null);
            }

            $out[$language->value] = $normalized;
        }

        return $out;
    }

    /**
     * Ein getrimmter String für jede Eingabe, auch für null und Nicht-Strings.
     */
    public static function text(mixed $value): string
    {
        return is_string($value) ? trim($value) : '';
    }

    /**
     * Eine Liste nicht-leerer, getrimmter Zeilen. Ein String wird an
     * Zeilenumbrüchen zerlegt, damit ein Textfeld direkt übergeben werden kann.
     *
     * @return array<int, string>
     */
    public static function lines(mixed $value): array
    {
        if (is_string($value)) {
            $value = preg_split('/\R/', $value) ?: [];
        }

        if (! is_array($value)) {
            return [];
        }

        $lines = [];
        foreach ($value as $line) {
            $line = self::text($line);
            if ($line !== '') {
                $lines[] = $line;
            }
        }

        return $lines;
    }
}
