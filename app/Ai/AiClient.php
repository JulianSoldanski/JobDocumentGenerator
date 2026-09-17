<?php

namespace App\Ai;

use App\Models\AiSetting;
use App\Models\User;
use Prism\Prism\Contracts\Schema;
use Prism\Prism\Enums\FinishReason;
use Prism\Prism\Facades\Prism;

/**
 * Die einzige Stelle, an der ein Modell angesprochen wird.
 *
 * Der Anbieter ist austauschbar (Prism), die Prompts liegen daneben als
 * Textdateien. Zwei Eigenheiten sind hier festgeschrieben:
 *
 * 1. Der Denkmodus wird abgeschaltet. Bleibt er an, verbraucht das Modell sein
 *    Token-Budget unsichtbar, bevor der eigentliche Text beginnt — lange
 *    Prompts kommen dann leer zurück.
 * 2. Eine abgeschnittene oder leere Antwort wird zum Fehler. Ein leerer Text,
 *    der als Ergebnis durchgereicht wird, fällt sonst erst im fertigen
 *    Dokument auf.
 *
 * Der Zugang gehört dem Nutzer: `using($user)` nimmt dessen Schlüssel, Anbieter
 * und Modell. Ohne Nutzer greift die Serverkonfiguration — praktisch für
 * Demo-Konten und den Rauchtest auf der Konsole.
 */
class AiClient
{
    private ?AiSetting $settings = null;

    public function __construct(private readonly PromptTemplate $prompts) {}

    /**
     * Derselbe Client, aber mit dem Zugang dieses Nutzers.
     */
    public function using(User $user): self
    {
        $client = clone $this;
        $client->settings = $user->aiAccess();

        return $client;
    }

    /**
     * True, sobald ein Schlüssel hinterlegt ist — egal ob beim Nutzer oder,
     * als Rückfallebene, in der Serverkonfiguration.
     */
    public function hasCredentials(): bool
    {
        return $this->apiKey() !== null;
    }

    /**
     * Freitext — etwa ein umgeschriebener Absatz.
     */
    public function text(string $prompt, int $maxTokens = 2048): string
    {
        $this->guardCredentials();

        $response = Prism::text()
            ->using($this->provider(), $this->model(), $this->providerConfig())
            ->withPrompt($prompt)
            ->withMaxTokens($maxTokens)
            ->withProviderOptions($this->providerOptions())
            ->asText();

        $this->guardFinishReason($response->finishReason);

        $text = trim($response->text);

        if ($text === '') {
            throw new AiException('Das Modell hat nichts zurückgegeben.');
        }

        return $text;
    }

    /**
     * Strukturierte Antwort nach Schema.
     *
     * @return array<string, mixed>
     */
    public function structured(string $prompt, Schema $schema, int $maxTokens = 4096): array
    {
        $this->guardCredentials();

        $response = Prism::structured()
            ->using($this->provider(), $this->model(), $this->providerConfig())
            ->withSchema($schema)
            ->withPrompt($prompt)
            ->withMaxTokens($maxTokens)
            ->withProviderOptions($this->providerOptions())
            ->asStructured();

        $this->guardFinishReason($response->finishReason);

        $structured = $response->structured;

        if (! is_array($structured) || $structured === []) {
            throw new AiException('Das Modell hat keine verwertbare Antwort zurückgegeben.');
        }

        return $structured;
    }

    public function prompts(): PromptTemplate
    {
        return $this->prompts;
    }

    public function provider(): string
    {
        $provider = trim((string) ($this->settings->provider ?? ''));

        return $provider !== '' ? $provider : (string) config('cvcreater.ai.provider');
    }

    public function model(): string
    {
        $model = trim((string) ($this->settings->model ?? ''));

        return $model !== '' ? $model : (string) config('cvcreater.ai.model');
    }

    /**
     * Der Schlüssel des Nutzers, sonst der aus der Serverkonfiguration.
     */
    public function apiKey(): ?string
    {
        $key = trim((string) ($this->settings->api_key ?? ''));

        if ($key !== '') {
            return $key;
        }

        $fallback = trim((string) config("prism.providers.{$this->provider()}.api_key", ''));

        return $fallback !== '' ? $fallback : null;
    }

    /**
     * Was Prism je Aufruf über den Anbieter wissen muss.
     *
     * @return array<string, mixed>
     */
    public function providerConfig(): array
    {
        $key = $this->apiKey();

        return $key === null ? [] : ['api_key' => $key];
    }

    /**
     * Die Anbieter-Optionen, die den Denkmodus abschalten.
     *
     * @return array<string, mixed>
     */
    public function providerOptions(): array
    {
        /** @var array<string, array<string, mixed>> $options */
        $options = config('cvcreater.ai.provider_options', []);

        return $options[$this->provider()] ?? [];
    }

    private function guardCredentials(): void
    {
        if (! $this->hasCredentials()) {
            throw new AiException(
                'Es ist kein API-Schlüssel hinterlegt. Trag ihn unter Profil → KI-Zugang ein.'
            );
        }
    }

    private function guardFinishReason(FinishReason $reason): void
    {
        if ($reason === FinishReason::Length) {
            throw new AiException(
                'Die Antwort wurde vom Token-Limit abgeschnitten. Prüfe, ob der Denkmodus des Modells wirklich aus ist.'
            );
        }

        if ($reason === FinishReason::Error || $reason === FinishReason::ContentFilter) {
            throw new AiException("Das Modell hat abgebrochen ({$reason->value}).");
        }
    }
}
