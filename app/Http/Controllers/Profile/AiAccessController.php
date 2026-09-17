<?php

namespace App\Http\Controllers\Profile;

use App\Ai\AiClient;
use App\Ai\AiException;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

/**
 * Der KI-Zugang: Anbieter, Modell und der eigene API-Schlüssel.
 *
 * Der Schlüssel wird verschlüsselt gespeichert und nie wieder ausgeliefert —
 * die Oberfläche sieht nur, ob einer hinterlegt ist und wie er endet.
 */
class AiAccessController extends Controller
{
    /** Anbieter, die Prism kennt und die hier sinnvoll sind. */
    private const PROVIDERS = ['gemini', 'anthropic', 'openai', 'mistral', 'openrouter', 'ollama'];

    public function edit(Request $request): Response
    {
        $access = $request->user()->aiAccess();
        $client = app(AiClient::class)->using($request->user());

        return Inertia::render('profile/ai', [
            'access' => [
                'provider' => $access->provider ?? (string) config('cvcreater.ai.provider'),
                'model' => $access->model ?? (string) config('cvcreater.ai.model'),
                'has_key' => $access->hasKey(),
                'key_hint' => $access->hint(),
                'verified_at' => $access->verified_at?->toIso8601String(),
                // Ohne eigenen Schlüssel kann trotzdem einer aus der
                // Serverkonfiguration greifen — das gehört sichtbar gemacht.
                'uses_server_key' => ! $access->hasKey() && $client->hasCredentials(),
            ],
            'providers' => self::PROVIDERS,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'provider' => ['required', 'string', 'in:'.implode(',', self::PROVIDERS)],
            'model' => ['required', 'string', 'max:100'],
            'api_key' => ['nullable', 'string', 'max:500'],
        ], [
            'provider.in' => 'Dieser Anbieter wird nicht unterstützt.',
            'model.required' => 'Ohne Modellnamen weiß der Anbieter nicht, was er rechnen soll.',
        ]);

        $access = $request->user()->aiAccess();
        $access->provider = $validated['provider'];
        $access->model = $validated['model'];

        // Ein leeres Feld heißt "nicht ändern" — sonst würde jedes Speichern
        // der Einstellungen den Schlüssel löschen.
        if (trim((string) ($validated['api_key'] ?? '')) !== '') {
            $access->api_key = trim($validated['api_key']);
            $access->verified_at = null;
        }

        $access->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'KI-Zugang gespeichert.']);

        return back();
    }

    public function destroy(Request $request): RedirectResponse
    {
        $access = $request->user()->aiAccess();
        $access->api_key = null;
        $access->verified_at = null;
        $access->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Schlüssel entfernt.']);

        return back();
    }

    /**
     * Ein kurzer echter Aufruf: billiger als eine fehlgeschlagene Generierung
     * und die einzige Art, einen Schlüssel wirklich zu prüfen.
     */
    public function test(Request $request, AiClient $ai): JsonResponse
    {
        $client = $ai->using($request->user());

        try {
            $answer = $client->text('Antworte mit genau einem Wort: OK', 32);
        } catch (AiException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        $access = $request->user()->aiAccess();
        $access->verified_at = now();
        $access->save();

        return response()->json([
            'ok' => true,
            'message' => "Verbindung steht ({$client->provider()} · {$client->model()}): „{$answer}\"",
        ]);
    }
}
