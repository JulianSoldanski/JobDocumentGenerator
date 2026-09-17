<?php

namespace App\Models\Concerns;

use App\Enums\Language;
use App\Support\Translations;

/**
 * Zweisprachige Einträge: je Sprache ein vom Nutzer geschriebener Textblock.
 *
 * Fehlt eine Sprache, greift **feldweise** die andere, damit ein halb
 * übersetzter Eintrag trotzdem vollständig gedruckt wird. Es wird nicht
 * heimlich übersetzt — der Leser sieht im Zweifel den Text, den der Nutzer
 * tatsächlich geschrieben hat.
 */
trait HasTranslations
{
    /** @return array<int, string> */
    abstract public function translatableTextFields(): array;

    /** @return array<int, string> */
    abstract public function translatableListFields(): array;

    /**
     * Das Feld, das ausgefüllt sein muss, damit eine Sprache als geschrieben gilt.
     */
    abstract public function headlineField(): string;

    /**
     * Der Textblock, den ein Renderer für diese Sprache drucken soll.
     *
     * @return array<string, string|array<int, string>>
     */
    public function localized(Language $language): array
    {
        $block = [];

        foreach ($this->translatableTextFields() as $field) {
            $block[$field] = $this->localizedText($language, $field);
        }

        foreach ($this->translatableListFields() as $field) {
            $block[$field] = $this->localizedList($language, $field);
        }

        return $block;
    }

    /**
     * Ein Textfeld in der gewünschten Sprache, sonst in der anderen.
     */
    public function localizedText(Language $language, string $field): string
    {
        $primary = Translations::text($this->translationBlock($language)[$field] ?? null);

        return $primary !== ''
            ? $primary
            : Translations::text($this->translationBlock($language->other())[$field] ?? null);
    }

    /**
     * Ein Listenfeld in der gewünschten Sprache, sonst in der anderen.
     *
     * @return array<int, string>
     */
    public function localizedList(Language $language, string $field): array
    {
        $primary = Translations::lines($this->translationBlock($language)[$field] ?? null);

        return $primary !== []
            ? $primary
            : Translations::lines($this->translationBlock($language->other())[$field] ?? null);
    }

    /**
     * Die Sprachen, in denen dieser Eintrag noch nicht geschrieben ist.
     *
     * @return array<int, Language>
     */
    public function missingLanguages(): array
    {
        $missing = [];

        foreach (Language::cases() as $language) {
            $headline = Translations::text($this->translationBlock($language)[$this->headlineField()] ?? null);
            if ($headline === '') {
                $missing[] = $language;
            }
        }

        return $missing;
    }

    /**
     * Der gespeicherte Block einer Sprache, ungefiltert.
     *
     * @return array<string, mixed>
     */
    public function translationBlock(Language $language): array
    {
        // Bewusst über getAttribute: vor dem Speichern kann hier stehen, was
        // ein Formular geschickt hat — die Form wird erst beim Speichern
        // hergestellt.
        $translations = $this->getAttribute('translations');
        $block = is_array($translations) ? ($translations[$language->value] ?? []) : [];

        return is_array($block) ? $block : [];
    }

    /**
     * Normalisiert die Blöcke beim Speichern — nicht über einen Mutator, weil
     * die Feldliste bei Profileinträgen vom Abschnitt abhängt und der beim
     * Zuweisen noch nicht gesetzt sein muss.
     */
    public static function bootHasTranslations(): void
    {
        static::saving(function (self $model): void {
            $model->setAttribute('translations', Translations::normalize(
                $model->getAttribute('translations'),
                $model->translatableTextFields(),
                $model->translatableListFields(),
            ));
        });
    }
}
