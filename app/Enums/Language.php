<?php

namespace App\Enums;

/**
 * Die beiden Sprachen, in denen ein Nutzer sein Profil schreibt und in denen
 * Dokumente entstehen. Übersetzt wird nie — fehlt eine Sprache, greift
 * feldweise die andere.
 */
enum Language: string
{
    case German = 'de';
    case English = 'en';

    public function other(): self
    {
        return $this === self::German ? self::English : self::German;
    }

    public function label(): string
    {
        return match ($this) {
            self::German => 'Deutsch',
            self::English => 'Englisch',
        };
    }
}
