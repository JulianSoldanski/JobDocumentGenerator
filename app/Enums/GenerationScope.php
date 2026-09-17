<?php

namespace App\Enums;

enum GenerationScope: string
{
    case Both = 'both';
    case CvOnly = 'cv';
    case LetterOnly = 'letter';

    public function includesCv(): bool
    {
        return $this !== self::LetterOnly;
    }

    public function includesLetter(): bool
    {
        return $this !== self::CvOnly;
    }

    public function label(): string
    {
        return match ($this) {
            self::Both => 'Lebenslauf und Anschreiben',
            self::CvOnly => 'nur Lebenslauf',
            self::LetterOnly => 'nur Anschreiben',
        };
    }
}
