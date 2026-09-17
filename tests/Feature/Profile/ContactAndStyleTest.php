<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactAndStyleTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_details_are_stored_once_per_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('profile.contact.update'), [
            'full_name' => 'Max Mustermann',
            'street' => 'Musterstraße 1',
            'postal_code' => '10115',
            'city' => 'Berlin',
            'phone' => '+49 30 12345678',
            'email' => 'bewerbung@example.com',
        ])->assertSessionHasNoErrors();

        $contact = $user->contact()->refresh();
        $this->assertSame('Max Mustermann', $contact->full_name);
        $this->assertSame(['Musterstraße 1', '10115 Berlin'], $contact->addressLines());
        $this->assertSame('Musterstraße 1 · 10115 Berlin', $contact->addressLine());
        $this->assertSame(1, $user->contactDetail()->count());
    }

    public function test_a_name_is_required_because_it_heads_every_document(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('profile.contact.update'), ['full_name' => ''])
            ->assertSessionHasErrors('full_name');
    }

    public function test_style_rules_are_stored_as_a_list(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('profile.style.update'), [
            'example' => 'Ein Beispieltext.',
            'rules' => ['Kurze Hauptsätze.', '   ', 'Keine Floskeln.'],
        ])->assertSessionHasNoErrors();

        $style = $user->style()->refresh();
        $this->assertSame('Ein Beispieltext.', $style->example);
        $this->assertSame(['Kurze Hauptsätze.', 'Keine Floskeln.'], $style->rules, 'Leere Zeilen fallen raus.');
    }
}
