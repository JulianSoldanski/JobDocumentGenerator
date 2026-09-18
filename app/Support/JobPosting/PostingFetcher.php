<?php

namespace App\Support\JobPosting;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Lädt eine Stellenanzeige und gibt sie als reinen Text zurück.
 *
 * Nicht jede Seite lässt sich laden — Portale mit Anmeldung oder solche, die
 * ihre Anzeige erst per JavaScript nachladen, liefern nichts Brauchbares.
 * Deshalb ist das Einfügen als Text gleichwertig und nicht die Notlösung.
 */
class PostingFetcher
{
    public function fetch(string $url): string
    {
        $this->guard($url);

        try {
            $response = Http::withHeaders([
                // Ohne erkennbaren Browser antworten viele Portale mit einer
                // Sperrseite statt mit der Anzeige.
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml',
                'Accept-Language' => 'de-DE,de;q=0.9,en;q=0.8',
            ])
                ->timeout((int) config('cvcreater.job_posting.fetch_timeout'))
                ->withOptions(['allow_redirects' => ['max' => 5, 'strict' => true]])
                ->get($url);
        } catch (ConnectionException) {
            throw new PostingFetchException(
                'Die Seite war nicht erreichbar. Füge die Anzeige als Text ein.'
            );
        }

        if ($response->failed()) {
            throw new PostingFetchException(
                "Die Seite hat mit {$response->status()} geantwortet. Füge die Anzeige als Text ein."
            );
        }

        $text = PostingText::limit(PostingText::fromHtml($response->body()));

        if (mb_strlen($text) < 200) {
            throw new PostingFetchException(
                'Auf der Seite stand kaum Text — vermutlich lädt sie die Anzeige erst im Browser nach. Füge sie als Text ein.'
            );
        }

        return $text;
    }

    /**
     * Die Adresse kommt vom Nutzer, geladen wird sie vom Server. Ohne diese
     * Prüfung ließe sich darüber das interne Netz abfragen.
     */
    private function guard(string $url): void
    {
        $host = parse_url($url, PHP_URL_HOST);
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        if (! in_array($scheme, ['http', 'https'], true) || ! is_string($host) || $host === '') {
            throw new PostingFetchException('Das ist keine vollständige Web-Adresse.');
        }

        // IPv6 steht in der Adresse in eckigen Klammern; die Prüfung braucht
        // die nackte Adresse.
        foreach ($this->addressesOf(trim($host, '[]')) as $address) {
            $public = filter_var(
                $address,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            );

            if ($public === false) {
                throw new PostingFetchException('Diese Adresse liegt im internen Netz und wird nicht geladen.');
            }
        }
    }

    /**
     * Getrennt von der Prüfung, damit ein Test sie ohne Namensauflösung
     * durchspielen kann.
     *
     * @return array<int, string>
     */
    protected function addressesOf(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return [$host];
        }

        $records = @dns_get_record($host, DNS_A | DNS_AAAA) ?: [];

        $addresses = array_values(array_filter(array_map(
            static fn (array $record): ?string => $record['ip'] ?? $record['ipv6'] ?? null,
            $records
        )));

        if ($addresses === []) {
            throw new PostingFetchException('Diese Adresse ließ sich nicht auflösen.');
        }

        return $addresses;
    }
}
