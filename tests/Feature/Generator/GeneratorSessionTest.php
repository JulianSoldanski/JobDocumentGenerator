<?php

namespace Tests\Feature\Generator;

use App\Models\GeneratorSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Die Sitzung liegt auf dem Server: Ein Neuladen bleibt dort, wo der Nutzer
 * war, und die Uhr läuft unabhängig vom Tab.
 */
class GeneratorSessionTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'language' => 'de',
            'layout' => 'modern',
            'scope' => 'both',
        ], $overrides);
    }

    public function test_guests_are_sent_to_the_login_page(): void
    {
        $this->get(route('generator.index'))->assertRedirect(route('login'));
    }

    public function test_the_generator_opens_with_a_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('generator.index'))->assertOk();

        $this->assertSame(1, $user->generatorSessions()->count());
    }

    public function test_the_last_session_is_reopened_instead_of_a_new_one(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('generator.index'))->assertOk();
        $this->actingAs($user)->get(route('generator.index'))->assertOk();

        $this->assertSame(1, $user->generatorSessions()->count());
    }

    public function test_fields_and_settings_are_saved(): void
    {
        $user = User::factory()->create();
        $session = GeneratorSession::factory()->for($user)->create();

        $this->actingAs($user)
            ->patch(route('generator.update', $session), $this->payload([
                'company' => 'MusterTech GmbH',
                'position' => 'Fullstack-Entwicklerin',
                'contact_person' => 'Frau Dr. Meyer',
                'city' => 'Hamburg',
                'company_address' => "Musterstraße 1\n20095 Hamburg",
                'language' => 'en',
                'layout' => 'sidebar',
                'scope' => 'letter',
                'notes' => 'Den Data-Teil betonen.',
            ]))
            ->assertRedirect();

        $session->refresh();
        $this->assertSame('MusterTech GmbH', $session->company);
        $this->assertSame('Frau Dr. Meyer', $session->contact_person);
        $this->assertSame('en', $session->language->value);
        $this->assertSame('sidebar', $session->layout->value);
        $this->assertSame('letter', $session->scope->value);
        $this->assertSame('Den Data-Teil betonen.', $session->notes);
    }

    /**
     * Der Alltagsfall: Die Adresse steht drin, alles andere ist noch leer.
     * Laravel macht aus leeren Eingaben `null`, die Spalten dafür sind aber
     * nicht nullable — ihr Leerwert ist der leere String.
     */
    public function test_emptied_fields_are_saved_as_empty_strings(): void
    {
        $user = User::factory()->create();
        $session = GeneratorSession::factory()->for($user)->create();

        $this->actingAs($user)
            ->patch(route('generator.update', $session), $this->payload([
                'job_url' => 'https://stark.jobs.personio.com/job/2764928',
                'job_posting' => '',
                'company' => '',
                'position' => '',
                'contact_person' => '',
                'city' => '',
                'company_address' => '',
                'notes' => '',
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $session->refresh();
        $this->assertSame('https://stark.jobs.personio.com/job/2764928', $session->job_url);
        $this->assertSame('', $session->company);
        $this->assertSame('', $session->position);
        $this->assertSame('', $session->contact_person);
        $this->assertSame('', $session->city);
    }

    /**
     * Eingefügter Text bekommt dieselbe Behandlung wie geladener — sonst sieht
     * das Modell je nach Weg eine andere Anzeige.
     */
    public function test_a_pasted_posting_is_cleaned_up(): void
    {
        $user = User::factory()->create();
        $session = GeneratorSession::factory()->for($user)->create();

        $this->actingAs($user)->patch(
            route('generator.update', $session),
            $this->payload(['job_posting' => "Wir    suchen\n\n\n\n   eine Entwicklerin.  "])
        );

        $this->assertSame("Wir suchen\n\neine Entwicklerin.", $session->refresh()->job_posting);
    }

    public function test_the_clock_starts_with_the_first_posting(): void
    {
        $user = User::factory()->create();
        $session = GeneratorSession::factory()->for($user)->create();

        $this->actingAs($user)->patch(
            route('generator.update', $session),
            $this->payload(['job_posting' => ''])
        );

        $this->assertNull($session->refresh()->timer_started_at, 'Ohne Anzeige ist keine Stelle identifiziert.');

        $this->actingAs($user)->patch(
            route('generator.update', $session),
            $this->payload(['job_posting' => 'Wir suchen eine Entwicklerin.'])
        );

        $started = $session->refresh()->timer_started_at;
        $this->assertNotNull($started);

        $this->actingAs($user)->patch(
            route('generator.update', $session),
            $this->payload(['job_posting' => 'Wir suchen eine Entwicklerin. Nachtrag.'])
        );

        $this->assertEquals(
            $started,
            $session->refresh()->timer_started_at,
            'Eine Korrektur an der Anzeige setzt die Uhr nicht zurück.'
        );
    }

    public function test_an_incomplete_address_is_rejected(): void
    {
        $user = User::factory()->create();
        $session = GeneratorSession::factory()->for($user)->create();

        $this->actingAs($user)
            ->patchJson(route('generator.update', $session), $this->payload(['job_url' => 'muster.de/job']))
            ->assertStatus(422);
    }

    public function test_a_session_of_another_user_stays_private(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $session = GeneratorSession::factory()->for($owner)->create();

        $this->actingAs($stranger)
            ->get(route('generator.show', $session))
            ->assertForbidden();

        $this->actingAs($stranger)
            ->patch(route('generator.update', $session), $this->payload(['company' => 'Fremd GmbH']))
            ->assertForbidden();

        $this->assertSame('', $session->refresh()->company);
    }
}
