<?php

namespace App\Support\JobPosting;

/**
 * Macht aus einer Adresse die Adresse der Stelle — ohne das, was nur
 * verrät, woher jemand kam.
 *
 * Dieselbe Anzeige kommt über LinkedIn, die Google-Suche und einen Newsletter
 * mit jeweils anderen Anhängseln herein. Ohne sie ist es dieselbe Adresse, und
 * die Stelle landet nur einmal in der Queue.
 */
class PostingUrl
{
    /** Parameter, die eine Herkunft markieren, keine Stelle. */
    private const TRACKING = [
        'gclid', 'gbraid', 'wbraid', 'dclid', 'fbclid', 'msclkid', 'yclid', 'igshid', 'si',
        'mc_cid', 'mc_eid', '_hsenc', '_hsmi', 'gh_src', 'ref', 'source', 'src',
        // LinkedIn
        'trk', 'trkinfo', 'trackingid', 'refid', 'lipi', 'ebp', 'originalsubdomain',
    ];

    public static function clean(string $url): string
    {
        $url = trim($url);
        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return $url;
        }

        // Von Hand statt mit parse_str: das würde Punkte in Namen umschreiben
        // und die Kodierung der übrigen Parameter verändern.
        $kept = array_filter(
            explode('&', $parts['query'] ?? ''),
            function (string $pair): bool {
                $name = strtolower(urldecode(explode('=', $pair, 2)[0]));

                return $name !== '' && ! str_starts_with($name, 'utm_') && ! in_array($name, self::TRACKING, true);
            },
        );

        return strtolower($parts['scheme']).'://'
            .strtolower($parts['host'])
            .(isset($parts['port']) ? ':'.$parts['port'] : '')
            .($parts['path'] ?? '/')
            .($kept === [] ? '' : '?'.implode('&', $kept));
    }
}
