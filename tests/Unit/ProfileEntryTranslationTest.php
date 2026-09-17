<?php

namespace Tests\Unit;

use App\Enums\Language;
use App\Enums\ProfileSection;
use App\Models\ProfileEntry;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Prinzip 2: Fehlt eine Sprache, greift feldweise die andere — damit ein halb
 * übersetzter Eintrag trotzdem vollständig gedruckt wird.
 */
class ProfileEntryTranslationTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $translations
     * @param  array<string, mixed>  $expected
     */
    #[DataProvider('fallbackCases')]
    public function test_fields_fall_back_to_the_other_language(array $translations, string $language, array $expected): void
    {
        $entry = new ProfileEntry(['section' => ProfileSection::Experience, 'translations' => $translations]);

        $localized = $entry->localized(Language::from($language));

        foreach ($expected as $field => $value) {
            $this->assertSame($value, $localized[$field], "Feld {$field}");
        }
    }

    /**
     * @return array<string, array{array<string, mixed>, string, array<string, mixed>}>
     */
    public static function fallbackCases(): array
    {
        $full = [
            'de' => ['title' => 'Werkstudent', 'location' => 'Berlin', 'bullets' => ['Erstens', 'Zweitens']],
            'en' => ['title' => 'Working Student', 'location' => 'Berlin, Germany', 'bullets' => ['First', 'Second']],
        ];

        return [
            'beide Sprachen, deutsch angefragt' => [$full, 'de', [
                'title' => 'Werkstudent', 'location' => 'Berlin', 'bullets' => ['Erstens', 'Zweitens'],
            ]],
            'beide Sprachen, englisch angefragt' => [$full, 'en', [
                'title' => 'Working Student', 'location' => 'Berlin, Germany', 'bullets' => ['First', 'Second'],
            ]],
            'englisch fehlt ganz' => [['de' => $full['de'], 'en' => []], 'en', [
                'title' => 'Werkstudent', 'location' => 'Berlin', 'bullets' => ['Erstens', 'Zweitens'],
            ]],
            'halb übersetzt: Titel englisch, Bullets nur deutsch' => [[
                'de' => $full['de'],
                'en' => ['title' => 'Working Student', 'location' => '', 'bullets' => []],
            ], 'en', [
                'title' => 'Working Student',
                'location' => 'Berlin',
                'bullets' => ['Erstens', 'Zweitens'],
            ]],
            'leere Zeichenkette zählt als fehlend' => [[
                'de' => ['title' => 'Werkstudent', 'location' => 'Berlin', 'bullets' => []],
                'en' => ['title' => '   ', 'location' => 'Berlin, Germany', 'bullets' => ['First']],
            ], 'en', [
                'title' => 'Werkstudent',
                'location' => 'Berlin, Germany',
                'bullets' => ['First'],
            ]],
            'nichts vorhanden' => [[], 'de', [
                'title' => '', 'location' => '', 'bullets' => [],
            ]],
        ];
    }

    public function test_missing_languages_key_off_the_headline_field(): void
    {
        $onlyGerman = new ProfileEntry([
            'section' => ProfileSection::Experience,
            'translations' => ['de' => ['title' => 'Werkstudent'], 'en' => ['location' => 'Berlin']],
        ]);

        $this->assertSame([Language::English], $onlyGerman->missingLanguages());

        $both = new ProfileEntry([
            'section' => ProfileSection::Education,
            'translations' => ['de' => ['degree' => 'B.Sc.'], 'en' => ['degree' => 'B.Sc.']],
        ]);

        $this->assertSame([], $both->missingLanguages());
    }

    public function test_sort_key_puts_current_positions_first(): void
    {
        $current = new ProfileEntry(['section' => ProfileSection::Experience, 'is_current' => true, 'start_month' => '2020-01']);
        $past = new ProfileEntry(['section' => ProfileSection::Experience, 'end_month' => '2025-06']);
        $undated = new ProfileEntry(['section' => ProfileSection::Experience]);

        $this->assertGreaterThan($past->sortKey(), $current->sortKey());
        $this->assertGreaterThan($undated->sortKey(), $past->sortKey());
    }
}
