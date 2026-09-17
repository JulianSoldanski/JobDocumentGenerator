<?php

namespace App\Documents;

use App\Enums\Language;
use Illuminate\Support\Carbon;

/**
 * Formatierungen, die in den Dokument-Vorlagen gebraucht werden.
 *
 * Sie hängen an der Sprache des Dokuments — nicht an der Spracheinstellung der
 * Oberfläche: ein englischer Lebenslauf schreibt "Sep 2024 – present", auch
 * wenn die App auf Deutsch bedient wird.
 */
class DocumentFormat
{
    /** @var array<string, mixed> */
    private array $strings;

    public function __construct(public readonly Language $language)
    {
        /** @var array<string, mixed> $strings */
        $strings = trans('documents', [], $language->value);
        $this->strings = $strings;
    }

    /**
     * Ein Monat als "Sep 2024".
     */
    public function month(?string $month): string
    {
        if (! is_string($month) || preg_match('/^(\d{4})-(\d{2})$/', $month, $parts) !== 1) {
            return '';
        }

        /** @var array<int, string> $months */
        $months = $this->strings['months'];

        return ($months[(int) $parts[2] - 1] ?? $parts[2]).' '.$parts[1];
    }

    /**
     * Ein Zeitraum als "Sep 2024 – heute". Fehlt beides, bleibt er leer —
     * besser kein Zeitraum als ein erfundener.
     */
    public function period(?string $start, ?string $end, bool $current = false): string
    {
        $from = $this->month($start);
        $to = $current ? (string) $this->strings['present'] : $this->month($end);

        if ($from === '' && $to === '') {
            return '';
        }

        if ($from === '') {
            return $to;
        }

        return $to === '' ? $from : "{$from} – {$to}";
    }

    public function date(?string $date): string
    {
        if (! is_string($date) || $date === '') {
            return '';
        }

        return Carbon::parse($date)->translatedFormat((string) $this->strings['date_format']);
    }

    /**
     * Die Adresse ohne Protokoll — auf Papier liest sich "github.com/…"
     * besser als die volle URL, der Link im PDF bleibt vollständig.
     */
    public function linkLabel(?string $url): string
    {
        return rtrim(preg_replace('#^https?://#', '', (string) $url) ?? '', '/');
    }

    /**
     * Ein Wert aus den Beschriftungen, etwa "cv.experience".
     */
    public function label(string $key): string
    {
        $value = data_get($this->strings, $key);

        return is_string($value) ? $value : $key;
    }
}
