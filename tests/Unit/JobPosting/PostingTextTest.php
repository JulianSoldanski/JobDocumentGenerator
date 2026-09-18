<?php

namespace Tests\Unit\JobPosting;

use App\Support\JobPosting\PostingText;
use Tests\TestCase;

/**
 * Was das Modell nicht lesen muss, kostet nur Tokens — und kann schaden: Aus
 * einer Fußzeile darf kein Ansprechpartner werden.
 */
class PostingTextTest extends TestCase
{
    public function test_navigation_scripts_and_footer_fall_away(): void
    {
        $html = <<<'HTML'
            <html><body>
                <nav><a href="/jobs">Alle Stellen</a></nav>
                <script>var tracking = 'Datensammler';</script>
                <style>.headline { color: red }</style>
                <main><p>Wir suchen eine Fullstack-Entwicklerin.</p></main>
                <footer>Kontakt: impressum@example.com</footer>
            </body></html>
            HTML;

        $text = PostingText::fromHtml($html);

        $this->assertStringContainsString('Wir suchen eine Fullstack-Entwicklerin.', $text);
        $this->assertStringNotContainsString('Alle Stellen', $text);
        $this->assertStringNotContainsString('Datensammler', $text);
        $this->assertStringNotContainsString('color: red', $text);
        $this->assertStringNotContainsString('impressum@example.com', $text);
    }

    /**
     * Auf vielen Stellenseiten steht der Positionstitel im `header`.
     */
    public function test_the_page_header_stays(): void
    {
        $text = PostingText::fromHtml(
            '<html><body><header><h1>Fullstack-Entwicklerin (m/w/d)</h1></header><p>Hamburg</p></body></html>'
        );

        $this->assertStringContainsString('Fullstack-Entwicklerin (m/w/d)', $text);
    }

    /**
     * Im Seitentitel steht meist Position und Unternehmen — er ist nützlich,
     * darf aber nicht am ersten Absatz kleben.
     */
    public function test_the_page_title_stands_on_its_own_line(): void
    {
        $text = PostingText::fromHtml(
            '<html><head><title>Product Manager | Jobs bei MusterTech</title></head>'
            .'<body><p>Zum Hauptinhalt springen</p></body></html>'
        );

        $this->assertSame("Product Manager | Jobs bei MusterTech\nZum Hauptinhalt springen", $text);
    }

    public function test_block_elements_become_line_breaks(): void
    {
        $text = PostingText::fromHtml(
            '<html><body><ul><li>Erste Aufgabe</li><li>Zweite Aufgabe</li></ul></body></html>'
        );

        $this->assertSame("Erste Aufgabe\nZweite Aufgabe", $text);
    }

    public function test_whitespace_is_reduced_without_losing_the_structure(): void
    {
        $text = PostingText::normalize("Erste   Zeile  \n\n\n\n   Zweite Zeile\u{00A0}hier   \n");

        $this->assertSame("Erste Zeile\n\nZweite Zeile hier", $text);
    }

    public function test_a_long_posting_is_cut_at_a_line_break(): void
    {
        $text = str_repeat("Eine Zeile mit Text.\n", 50);

        $cut = PostingText::limit($text, 100);

        $this->assertLessThanOrEqual(100, mb_strlen($cut));
        $this->assertStringEndsWith('Text.', $cut);
    }

    public function test_an_empty_page_stays_empty(): void
    {
        $this->assertSame('', PostingText::fromHtml(''));
    }
}
