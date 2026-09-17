<?php

namespace App\Ai;

use Illuminate\Support\Facades\File;

/**
 * Die Prompts liegen als einzelne Textdateien neben dem Code, damit sie
 * geändert werden können, ohne Anwendungslogik anzufassen.
 *
 * Platzhalter sind `{{ name }}`. Fehlt einer beim Rendern, ist das ein Fehler
 * und keine stille Lücke im Prompt — ein umbenannter Wert soll auffallen,
 * bevor er das Modell erreicht.
 */
class PromptTemplate
{
    /**
     * @param  array<string, string|int|float>  $values
     */
    public function render(string $name, array $values = []): string
    {
        $prompt = $this->read($name);

        $rendered = preg_replace_callback(
            '/\{\{\s*([a-z0-9_]+)\s*\}\}/i',
            function (array $match) use ($name, $values): string {
                if (! array_key_exists($match[1], $values)) {
                    throw new AiException(
                        "Im Prompt \"{$name}\" fehlt der Wert für den Platzhalter \"{$match[1]}\"."
                    );
                }

                return (string) $values[$match[1]];
            },
            $prompt
        );

        return trim((string) $rendered);
    }

    public function path(string $name): string
    {
        return resource_path("prompts/{$name}.md");
    }

    private function read(string $name): string
    {
        $path = $this->path($name);

        if (! File::exists($path)) {
            throw new AiException("Der Prompt \"{$name}\" existiert nicht ({$path}).");
        }

        // Bewusst bei jedem Aufruf gelesen: ein Prompt lässt sich damit
        // ändern und erneut ausprobieren, ohne den Server neu zu starten.
        return File::get($path);
    }
}
