<?php

namespace Tests\Feature\Ai;

use App\Ai\AiClient;
use App\Ai\AiException;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\TextResponseFake;
use Tests\TestCase;

/**
 * Der KI-Zugang gehört dem Nutzer: eigener Schlüssel, eigener Anbieter, eigene
 * Kosten.
 */
class AiAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_key_is_stored_encrypted(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('profile.ai.update'), [
            'provider' => 'gemini',
            'model' => 'gemini-2.5-flash',
            'api_key' => 'geheim-1234',
        ])->assertRedirect();

        $stored = DB::table('ai_settings')->where('user_id', $user->id)->value('api_key');

        $this->assertNotNull($stored);
        $this->assertStringNotContainsString('geheim-1234', (string) $stored);
        $this->assertSame('geheim-1234', $user->aiAccess()->refresh()->api_key);
    }

    public function test_the_key_never_reaches_the_browser(): void
    {
        $user = User::factory()->create();
        $user->aiAccess()->fill(['provider' => 'gemini', 'model' => 'x', 'api_key' => 'geheim-1234'])->save();

        $response = $this->actingAs($user)->get(route('profile.ai'));

        $response->assertOk();
        $response->assertDontSee('geheim-1234');
        $response->assertInertia(fn ($page) => $page
            ->component('profile/ai')
            ->where('access.has_key', true)
            ->where('access.key_hint', '…1234')
            ->missing('access.api_key')
        );
    }

    public function test_saving_settings_without_a_key_keeps_the_stored_one(): void
    {
        $user = User::factory()->create();
        $user->aiAccess()->fill(['provider' => 'gemini', 'model' => 'alt', 'api_key' => 'geheim-1234'])->save();

        $this->actingAs($user)->put(route('profile.ai.update'), [
            'provider' => 'gemini',
            'model' => 'neu',
            'api_key' => '',
        ])->assertRedirect();

        $access = $user->aiAccess()->refresh();
        $this->assertSame('neu', $access->model);
        $this->assertSame('geheim-1234', $access->api_key);
    }

    public function test_the_key_can_be_removed(): void
    {
        $user = User::factory()->create();
        $user->aiAccess()->fill(['api_key' => 'geheim-1234'])->save();

        $this->actingAs($user)->delete(route('profile.ai.destroy'))->assertRedirect();

        $this->assertFalse($user->aiAccess()->refresh()->hasKey());
    }

    public function test_calls_run_with_the_users_own_key_and_model(): void
    {
        config(['prism.providers.gemini.api_key' => '']);
        $user = User::factory()->create();
        $user->aiAccess()->fill([
            'provider' => 'gemini',
            'model' => 'gemini-eigenes-modell',
            'api_key' => 'schluessel-des-nutzers',
        ])->save();

        $fake = Prism::fake([TextResponseFake::make()->withText('OK')]);

        app(AiClient::class)->using($user)->text('Ein Prompt');

        $fake->assertProviderConfig(['api_key' => 'schluessel-des-nutzers']);
        $fake->assertRequest(function (array $requests): void {
            $this->assertSame('gemini-eigenes-modell', $requests[0]->model());
        });
    }

    public function test_without_any_key_no_call_is_made(): void
    {
        config(['prism.providers.gemini.api_key' => '']);
        $user = User::factory()->create();

        $fake = Prism::fake([TextResponseFake::make()->withText('OK')]);

        $this->expectException(AiException::class);
        $this->expectExceptionMessage('KI-Zugang');

        try {
            app(AiClient::class)->using($user)->text('Ein Prompt');
        } finally {
            $fake->assertCallCount(0);
        }
    }

    public function test_ai_endpoints_refuse_before_queuing_anything(): void
    {
        config(['prism.providers.gemini.api_key' => '']);
        Queue::fake();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('profile.style.analyze'), [
                'example' => str_repeat('Ein Beispieltext, der lang genug ist. ', 10),
            ])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Für KI-Funktionen brauchst du einen eigenen API-Schlüssel. Trag ihn unter Profil → KI-Zugang ein.']);

        Queue::assertNothingPushed();
        $this->assertSame(0, $user->aiTasks()->count());
    }

    public function test_the_connection_test_marks_a_working_key(): void
    {
        $user = User::factory()->create();
        $user->aiAccess()->fill(['provider' => 'gemini', 'model' => 'x', 'api_key' => 'schluessel'])->save();

        Prism::fake([TextResponseFake::make()->withText('OK')]);

        $this->actingAs($user)
            ->postJson(route('profile.ai.test'))
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertNotNull($user->aiAccess()->refresh()->verified_at);
    }

    public function test_a_broken_key_is_reported_instead_of_thrown(): void
    {
        $user = User::factory()->create();
        $user->aiAccess()->fill(['provider' => 'gemini', 'model' => 'x', 'api_key' => 'falsch'])->save();

        Prism::fake([TextResponseFake::make()->withText('')]);

        $this->actingAs($user)
            ->postJson(route('profile.ai.test'))
            ->assertStatus(422)
            ->assertJson(['ok' => false]);

        $this->assertNull($user->aiAccess()->refresh()->verified_at);
    }

    public function test_settings_of_other_users_are_untouchable(): void
    {
        $owner = User::factory()->create();
        $owner->aiAccess()->fill(['api_key' => 'geheim'])->save();
        $stranger = User::factory()->create();

        $this->actingAs($stranger)->put(route('profile.ai.update'), [
            'provider' => 'openai',
            'model' => 'gpt-5',
            'api_key' => 'fremd',
        ])->assertRedirect();

        $this->assertSame('geheim', $owner->aiAccess()->refresh()->api_key);
        $this->assertSame('fremd', $stranger->aiAccess()->refresh()->api_key);
    }
}
