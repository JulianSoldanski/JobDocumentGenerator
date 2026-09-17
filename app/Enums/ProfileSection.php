<?php

namespace App\Enums;

enum ProfileSection: string
{
    case Experience = 'experience';
    case Education = 'education';
    case HardSkill = 'hard_skill';
    case SoftSkill = 'soft_skill';
    case LanguageSkill = 'language';

    /**
     * Abschnitte mit Zeitraum und Sichtbarkeitsschalter.
     *
     * @return array<int, self>
     */
    public static function dated(): array
    {
        return [self::Experience, self::Education];
    }

    /**
     * Abschnitte, die als einfache geordnete Liste gepflegt werden.
     *
     * @return array<int, self>
     */
    public static function lists(): array
    {
        return [self::HardSkill, self::SoftSkill, self::LanguageSkill];
    }

    public function isDated(): bool
    {
        return in_array($this, self::dated(), true);
    }

    /**
     * Das Feld, das ausgefüllt sein muss, damit eine Sprache als geschrieben
     * gilt — es entscheidet über das "EN fehlt"-Kennzeichen.
     */
    public function headlineField(): string
    {
        return match ($this) {
            self::Experience => 'title',
            self::Education => 'degree',
            default => 'name',
        };
    }

    /**
     * Sprachabhängige Textfelder des Abschnitts.
     *
     * @return array<int, string>
     */
    public function textFields(): array
    {
        return match ($this) {
            self::Experience => ['title', 'location'],
            self::Education => ['degree', 'location'],
            self::LanguageSkill => ['name', 'level'],
            default => ['name'],
        };
    }

    /**
     * Sprachabhängige Listenfelder des Abschnitts.
     *
     * @return array<int, string>
     */
    public function listFields(): array
    {
        return match ($this) {
            self::Experience => ['bullets'],
            self::Education => ['details'],
            default => [],
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Experience => 'Berufserfahrung',
            self::Education => 'Ausbildung',
            self::HardSkill => 'Hard Skills',
            self::SoftSkill => 'Soft Skills',
            self::LanguageSkill => 'Sprachen',
        };
    }
}
