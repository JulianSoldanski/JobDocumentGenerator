<?php

namespace Tests\Feature;

use App\Enums\ProfileSection;
use App\Models\ProfileEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfilePersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_translations_are_normalised_on_save(): void
    {
        $entry = ProfileEntry::factory()->create([
            'section' => ProfileSection::Experience,
            'translations' => [
                'de' => ['title' => '  Werkstudent  ', 'bullets' => "Erste Zeile\n\nZweite Zeile\n"],
            ],
        ]);

        $entry->refresh();

        $this->assertSame('Werkstudent', $entry->translations['de']['title']);
        $this->assertSame(['Erste Zeile', 'Zweite Zeile'], $entry->translations['de']['bullets']);
        $this->assertSame('', $entry->translations['en']['title'], 'Die andere Sprache bekommt die volle Form.');
        $this->assertSame([], $entry->translations['en']['bullets']);
    }

    public function test_skill_entries_only_keep_their_own_fields(): void
    {
        $skill = ProfileEntry::factory()->skill(ProfileSection::LanguageSkill, 'Deutsch', 'German')->create([
            'translations' => [
                'de' => ['name' => 'Deutsch', 'level' => 'Muttersprache', 'bullets' => ['gehört hier nicht hin']],
                'en' => ['name' => 'German', 'level' => 'Native'],
            ],
        ]);

        $skill->refresh();

        $this->assertSame(['name' => 'Deutsch', 'level' => 'Muttersprache'], $skill->translations['de']);
    }
}
