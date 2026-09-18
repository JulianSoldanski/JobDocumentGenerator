<?php

namespace Tests\Feature\Profile;

use App\Enums\ProfileSection;
use App\Models\ProfileEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_entry_written_in_one_language_is_enough(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('profile.entries.store'), [
            'section' => 'experience',
            'organization' => 'MusterTech GmbH',
            'start_month' => '2024-09',
            'is_current' => true,
            'translations' => [
                'de' => ['title' => 'Werkstudent', 'bullets' => "Erstens\nZweitens"],
                'en' => [],
            ],
        ]);

        $response->assertRedirect();

        $entry = $user->profileEntries()->sole();
        $this->assertSame('Werkstudent', $entry->translations['de']['title']);
        $this->assertSame(['Erstens', 'Zweitens'], $entry->translations['de']['bullets']);
        $this->assertNull($entry->end_month, 'Eine laufende Station hat kein Enddatum.');
    }

    /**
     * Nur eine Station kann leiser erscheinen — bei einer Ausbildung gibt es
     * den Schalter nicht.
     */
    public function test_a_position_can_be_marked_as_less_prominent(): void
    {
        $user = User::factory()->create();

        foreach (['experience' => 'Nebenjob', 'education' => 'Abitur'] as $section => $headline) {
            $this->actingAs($user)->post(route('profile.entries.store'), [
                'section' => $section,
                'organization' => 'Irgendwo',
                'is_subtle' => true,
                'translations' => ['de' => ['title' => $headline, 'degree' => $headline], 'en' => []],
            ])->assertRedirect();
        }

        $this->assertTrue($user->profileEntries()->where('section', 'experience')->sole()->is_subtle);
        $this->assertFalse($user->profileEntries()->where('section', 'education')->sole()->is_subtle);
    }

    public function test_an_entry_without_any_headline_is_rejected(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('profile.entries.store'), [
            'section' => 'experience',
            'organization' => 'MusterTech GmbH',
            'translations' => ['de' => ['title' => ''], 'en' => ['title' => '']],
        ]);

        $response->assertSessionHasErrors('translations.de.title');
        $this->assertSame(0, $user->profileEntries()->count());
    }

    public function test_experience_requires_a_company(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('profile.entries.store'), [
            'section' => 'experience',
            'organization' => '',
            'translations' => ['de' => ['title' => 'Werkstudent']],
        ]);

        $response->assertSessionHasErrors('organization');
    }

    public function test_a_skill_needs_no_company(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('profile.entries.store'), [
            'section' => 'hard_skill',
            'translations' => ['de' => ['name' => 'Python'], 'en' => ['name' => 'Python']],
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, $user->profileEntries()->count());
    }

    public function test_visibility_can_be_toggled_without_touching_the_texts(): void
    {
        $user = User::factory()->create();
        $entry = ProfileEntry::factory()->for($user)->create();

        $this->actingAs($user)
            ->patch(route('profile.entries.visibility', $entry), ['is_visible' => false])
            ->assertRedirect();

        $entry->refresh();
        $this->assertFalse($entry->is_visible);
        $this->assertSame('Werkstudent Softwareentwicklung', $entry->translations['de']['title']);
    }

    public function test_lists_can_be_reordered(): void
    {
        $user = User::factory()->create();
        $first = ProfileEntry::factory()->for($user)->skill()->create(['position' => 0]);
        $second = ProfileEntry::factory()->for($user)->skill()->create(['position' => 1]);

        $this->actingAs($user)->post(route('profile.entries.reorder'), [
            'section' => 'hard_skill',
            'ids' => [$second->id, $first->id],
        ])->assertRedirect();

        $this->assertSame(1, $first->fresh()->position);
        $this->assertSame(0, $second->fresh()->position);
    }

    public function test_entries_of_other_users_are_out_of_reach(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $entry = ProfileEntry::factory()->for($owner)->create();

        $this->actingAs($stranger)
            ->patch(route('profile.entries.update', $entry), [
                'section' => 'experience',
                'organization' => 'Fremd GmbH',
                'translations' => ['de' => ['title' => 'Übernommen']],
            ])
            ->assertForbidden();

        $this->actingAs($stranger)
            ->delete(route('profile.entries.destroy', $entry))
            ->assertForbidden();

        $this->assertSame('Werkstudent Softwareentwicklung', $entry->fresh()->translations['de']['title']);
    }

    public function test_the_experience_page_sorts_current_positions_first(): void
    {
        $user = User::factory()->create();
        ProfileEntry::factory()->for($user)->create([
            'is_current' => false,
            'end_month' => '2023-06',
            'translations' => ['de' => ['title' => 'Älter']],
        ]);
        ProfileEntry::factory()->for($user)->create([
            'is_current' => true,
            'end_month' => null,
            'translations' => ['de' => ['title' => 'Laufend']],
        ]);

        $this->actingAs($user)
            ->get(route('profile.experience'))
            ->assertInertia(fn ($page) => $page
                ->component('profile/experience')
                ->where('entries.0.headline', 'Laufend')
                ->where('entries.1.headline', 'Älter')
            );
    }

    public function test_sections_are_kept_apart(): void
    {
        $user = User::factory()->create();
        ProfileEntry::factory()->for($user)->create();
        ProfileEntry::factory()->for($user)->education()->create();
        ProfileEntry::factory()->for($user)->skill(ProfileSection::SoftSkill, 'Teamarbeit', 'Teamwork')->create();

        $this->actingAs($user)
            ->get(route('profile.skills'))
            ->assertInertia(fn ($page) => $page
                ->component('profile/skills')
                ->has('hardSkills', 0)
                ->has('softSkills', 1)
                ->has('languages', 0)
            );
    }
}
