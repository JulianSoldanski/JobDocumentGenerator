<?php

namespace Tests\Unit\Ai;

use App\Ai\AiException;
use App\Ai\PromptTemplate;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PromptTemplateTest extends TestCase
{
    public function test_placeholders_are_filled(): void
    {
        $this->writePrompt('test_prompt', "Hallo {{ name }},\n{{ anzahl }} Punkte.");

        $rendered = (new PromptTemplate)->render('test_prompt', ['name' => 'Welt', 'anzahl' => 3]);

        $this->assertSame("Hallo Welt,\n3 Punkte.", $rendered);
    }

    public function test_a_missing_value_fails_loudly(): void
    {
        $this->writePrompt('test_prompt', 'Hallo {{ name }}.');

        $this->expectException(AiException::class);
        $this->expectExceptionMessage('fehlt der Wert für den Platzhalter "name"');

        (new PromptTemplate)->render('test_prompt', []);
    }

    public function test_a_missing_prompt_file_fails_loudly(): void
    {
        $this->expectException(AiException::class);

        (new PromptTemplate)->render('gibt_es_nicht');
    }

    public function test_the_real_prompts_exist(): void
    {
        foreach (['style_analysis', 'project_draft', 'cv_import'] as $name) {
            $this->assertFileExists((new PromptTemplate)->path($name));
        }
    }

    protected function tearDown(): void
    {
        File::delete((new PromptTemplate)->path('test_prompt'));

        parent::tearDown();
    }

    private function writePrompt(string $name, string $content): void
    {
        File::put((new PromptTemplate)->path($name), $content);
    }
}
