<?php

namespace App\Support\Pdf;

use RuntimeException;
use Smalot\PdfParser\Parser;

/**
 * Text aus einer hochgeladenen PDF-Datei.
 */
class PdfText
{
    public function extract(string $path): string
    {
        try {
            $text = (new Parser)->parseFile($path)->getText();
        } catch (\Throwable $e) {
            throw new RuntimeException('Die PDF-Datei konnte nicht gelesen werden: '.$e->getMessage(), previous: $e);
        }

        // Aus PDFs kommen viele harte Umbrüche und doppelte Leerzeichen; für
        // das Modell zählt der Fließtext, nicht das Satzbild.
        $text = preg_replace("/[ \t]+/", ' ', $text) ?? '';
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? '';
        $text = trim($text);

        if ($text === '') {
            throw new RuntimeException(
                'Aus dieser PDF-Datei ließ sich kein Text lesen. Enthält sie nur Bilder, hilft nur eine Textfassung.'
            );
        }

        return $text;
    }
}
