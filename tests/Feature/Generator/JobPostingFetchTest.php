<?php

namespace Tests\Feature\Generator;

use App\Models\GeneratorSession;
use App\Models\User;
use App\Support\JobPosting\PostingFetcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Eine Anzeige lässt sich laden — oder eben nicht. Beides muss der Nutzer
 * verstehen, ohne ins Log zu schauen.
 */
class JobPostingFetchTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Die Namensauflösung gehört nicht in einen Test: Der Schutz vor Adressen
     * im internen Netz wird unten mit einer wörtlichen IP geprüft.
     */
    private function withoutDns(): void
    {
        $this->app->bind(PostingFetcher::class, fn (): PostingFetcher => new class extends PostingFetcher
        {
            /** @return array<int, string> */
            protected function addressesOf(string $host): array
            {
                return ['93.184.216.34'];
            }
        });
    }

    public function test_a_loaded_page_lands_as_plain_text_in_the_session(): void
    {
        $this->withoutDns();

        Http::fake(['*' => Http::response(
            '<html><body><nav>Alle Stellen</nav><main><p>'
            .str_repeat('Wir suchen eine Fullstack-Entwicklerin für Hamburg. ', 10)
            .'</p></main><footer>impressum@example.com</footer></body></html>'
        )]);

        $user = User::factory()->create();
        $session = GeneratorSession::factory()->for($user)->create();

        $response = $this->actingAs($user)->postJson(
            route('generator.posting', $session),
            ['url' => 'https://muster.example/job/42']
        );

        $response->assertOk();
        $this->assertStringContainsString('Fullstack-Entwicklerin', $response->json('posting'));
        $this->assertStringNotContainsString('Alle Stellen', $response->json('posting'));

        $session->refresh();
        $this->assertStringContainsString('Fullstack-Entwicklerin', (string) $session->job_posting);
        $this->assertSame('https://muster.example/job/42', $session->job_url);
        $this->assertNotNull($session->timer_started_at, 'Mit der Anzeige ist die Stelle identifiziert.');
    }

    public function test_a_page_that_only_loads_in_the_browser_says_so(): void
    {
        $this->withoutDns();
        Http::fake(['*' => Http::response('<html><body><div id="app"></div></body></html>')]);

        $user = User::factory()->create();
        $session = GeneratorSession::factory()->for($user)->create();

        $this->actingAs($user)
            ->postJson(route('generator.posting', $session), ['url' => 'https://muster.example/job/42'])
            ->assertStatus(422)
            ->assertJsonPath('message', fn (string $message): bool => str_contains($message, 'als Text ein'));

        $this->assertNull($session->refresh()->job_posting);
    }

    public function test_an_unreachable_page_is_not_a_crash(): void
    {
        $this->withoutDns();
        Http::fake(['*' => Http::response('Nicht gefunden', 404)]);

        $user = User::factory()->create();
        $session = GeneratorSession::factory()->for($user)->create();

        $this->actingAs($user)
            ->postJson(route('generator.posting', $session), ['url' => 'https://muster.example/job/42'])
            ->assertStatus(422);
    }

    /**
     * Die Adresse kommt vom Nutzer, geladen wird sie vom Server — ohne diese
     * Grenze ließe sich darüber das interne Netz abfragen.
     */
    public function test_the_internal_network_stays_out_of_reach(): void
    {
        Http::fake();

        $user = User::factory()->create();
        $session = GeneratorSession::factory()->for($user)->create();

        foreach (['http://127.0.0.1/job', 'http://192.168.1.10/job', 'http://[::1]/job'] as $url) {
            $this->actingAs($user)
                ->postJson(route('generator.posting', $session), ['url' => $url])
                ->assertStatus(422);
        }

        Http::assertNothingSent();
    }

    public function test_a_session_of_another_user_stays_private(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $session = GeneratorSession::factory()->for($owner)->create();

        $this->actingAs($stranger)
            ->postJson(route('generator.posting', $session), ['url' => 'https://muster.example/job/42'])
            ->assertForbidden();
    }
}
