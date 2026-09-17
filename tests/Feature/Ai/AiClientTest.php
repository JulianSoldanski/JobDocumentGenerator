<?php

namespace Tests\Feature\Ai;

use App\Ai\AiClient;
use App\Ai\AiException;
use App\Ai\Schemas\StyleSchema;
use Prism\Prism\Enums\FinishReason;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\StructuredResponseFake;
use Prism\Prism\Testing\TextResponseFake;
use Tests\TestCase;

class AiClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ohne Nutzerkontext greift der Schlüssel aus der Serverkonfiguration.
        config(['prism.providers.gemini.api_key' => 'test-schluessel']);
    }

    /**
     * Der Denkmodus muss aus sein: bleibt er an, verbraucht das Modell sein
     * Budget unsichtbar und lange Prompts kommen leer zurück.
     */
    public function test_thinking_is_switched_off_for_the_configured_provider(): void
    {
        config(['cvcreater.ai.provider' => 'gemini']);
        $fake = Prism::fake([TextResponseFake::make()->withText('Antwort')]);

        app(AiClient::class)->text('Ein Prompt');

        $fake->assertRequest(function (array $requests): void {
            $this->assertSame(0, $requests[0]->providerOptions('thinkingBudget'));
        });
    }

    public function test_a_truncated_answer_is_an_error(): void
    {
        Prism::fake([
            TextResponseFake::make()->withText('Anfang …')->withFinishReason(FinishReason::Length),
        ]);

        $this->expectException(AiException::class);
        $this->expectExceptionMessage('Token-Limit');

        app(AiClient::class)->text('Ein Prompt');
    }

    public function test_an_empty_answer_is_an_error(): void
    {
        Prism::fake([TextResponseFake::make()->withText('   ')]);

        $this->expectException(AiException::class);

        app(AiClient::class)->text('Ein Prompt');
    }

    public function test_structured_answers_come_back_as_an_array(): void
    {
        Prism::fake([
            StructuredResponseFake::make()->withStructured(['rules' => ['Kurze Sätze.']]),
        ]);

        $result = app(AiClient::class)->structured(
            'Ein Prompt',
            StyleSchema::make(),
        );

        $this->assertSame(['rules' => ['Kurze Sätze.']], $result);
    }

    public function test_an_empty_structured_answer_is_an_error(): void
    {
        Prism::fake([StructuredResponseFake::make()->withStructured([])]);

        $this->expectException(AiException::class);

        app(AiClient::class)->structured('Ein Prompt', StyleSchema::make());
    }
}
