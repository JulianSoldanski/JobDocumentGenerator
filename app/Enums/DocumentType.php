<?php

namespace App\Enums;

enum DocumentType: string
{
    case Cv = 'cv';
    case Letter = 'letter';
    case ProjectList = 'project_list';

    public function label(): string
    {
        return match ($this) {
            self::Cv => 'Lebenslauf',
            self::Letter => 'Anschreiben',
            self::ProjectList => 'Projektliste',
        };
    }
}
