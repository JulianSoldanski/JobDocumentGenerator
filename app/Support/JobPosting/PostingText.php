<?php

namespace App\Support\JobPosting;

use DOMDocument;
use DOMNode;
use DOMText;
use DOMXPath;

/**
 * Aus einer Stellenanzeige wird reiner Text.
 *
 * Was hier wegfällt, muss das Modell später nicht lesen: Skripte, Stile,
 * Navigation, Fußzeile, Cookie-Banner. Das spart Tokens und verhindert, dass
 * aus einer Fußzeile ein "Ansprechpartner" wird.
 *
 * `header` bleibt bewusst stehen — auf vielen Stellenseiten steht der
 * Positionstitel genau dort.
 */
class PostingText
{
    /** Ganze Teilbäume, die in einer Stellenanzeige nichts beitragen. */
    private const DROP = [
        'script', 'style', 'noscript', 'template', 'svg', 'iframe', 'object',
        'nav', 'footer', 'aside', 'form', 'button', 'select', 'video', 'audio',
    ];

    /** Elemente, nach denen ein Zeilenumbruch gehört. */
    private const BLOCKS = [
        'address', 'article', 'blockquote', 'br', 'dd', 'div', 'dl', 'dt',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'hr', 'li', 'ol', 'p', 'pre',
        'section', 'table', 'td', 'th', 'title', 'tr', 'ul',
    ];

    /**
     * HTML einer geladenen Seite als Text.
     */
    public static function fromHtml(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $document = new DOMDocument;

        // Die Fehler des Parsers interessieren nicht: echte Seiten sind selten
        // wohlgeformt, und für reinen Text reicht, was libxml daraus macht.
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="utf-8" ?>'.$html,
            LIBXML_NOERROR | LIBXML_NOWARNING
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new DOMXPath($document);

        $drop = implode(' or ', array_map(
            static fn (string $tag): string => "self::{$tag}",
            self::DROP
        ));

        // Zusätzlich alles, was sich selbst als Navigation ausweist oder für
        // Bildschirmleser versteckt ist.
        $query = "//*[{$drop}]|//*[@role='navigation']|//*[@aria-hidden='true']|//*[@hidden]";

        /** @var iterable<DOMNode> $unwanted */
        $unwanted = $xpath->query($query) ?: [];

        foreach (iterator_to_array($unwanted) as $node) {
            $node->parentNode?->removeChild($node);
        }

        $text = '';
        self::collect($document->documentElement ?? $document, $text);

        return self::normalize($text);
    }

    /**
     * Eingefügter Text bekommt dieselbe Behandlung wie geladener: gleiche
     * Grundlage für das Modell, egal woher die Anzeige kam.
     */
    public static function normalize(string $text): string
    {
        // Geschützte Leerzeichen und Zeilenendungen vereinheitlichen.
        $text = str_replace(["\xC2\xA0", "\r\n", "\r"], [' ', "\n", "\n"], $text);

        $lines = array_map(
            static fn (string $line): string => trim((string) preg_replace('/[ \t]+/u', ' ', $line)),
            explode("\n", $text)
        );

        $text = implode("\n", $lines);

        // Aus beliebig vielen Leerzeilen wird höchstens eine.
        return trim((string) preg_replace("/\n{3,}/", "\n\n", $text));
    }

    /**
     * Kürzt auf das, was in einen Prompt gehört — an einer Zeilengrenze, damit
     * kein Satz mitten im Wort abbricht.
     */
    public static function limit(string $text, ?int $max = null): string
    {
        $max = $max ?? (int) config('cvcreater.job_posting.max_length');

        if ($max <= 0 || mb_strlen($text) <= $max) {
            return $text;
        }

        $cut = mb_substr($text, 0, $max);
        $break = mb_strrpos($cut, "\n");

        return rtrim($break !== false && $break > $max / 2 ? mb_substr($cut, 0, $break) : $cut);
    }

    private static function collect(DOMNode $node, string &$text): void
    {
        if ($node instanceof DOMText) {
            $text .= $node->nodeValue ?? '';

            return;
        }

        foreach ($node->childNodes as $child) {
            self::collect($child, $text);
        }

        if (in_array(strtolower($node->nodeName), self::BLOCKS, true)) {
            $text .= "\n";
        }
    }
}
