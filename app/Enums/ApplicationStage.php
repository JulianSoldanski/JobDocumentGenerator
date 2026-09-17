<?php

namespace App\Enums;

/**
 * Die Stufen einer Bewerbung. "rejected" ist keine sechste Stufe, sondern ein
 * Abbruch: Der zurückgelegte Weg bleibt in der Historie sichtbar.
 */
enum ApplicationStage: string
{
    case Created = 'created';
    case Sent = 'sent';
    case Interview1 = 'interview_1';
    case Interview2 = 'interview_2';
    case Interview3 = 'interview_3';
    case Rejected = 'rejected';

    /**
     * Die Stufen in ihrer Reihenfolge, ohne die Absage.
     *
     * @return array<int, self>
     */
    public static function linear(): array
    {
        return [self::Created, self::Sent, self::Interview1, self::Interview2, self::Interview3];
    }

    public function isLinear(): bool
    {
        return $this !== self::Rejected;
    }

    /**
     * Position in der linearen Abfolge, oder null für die Absage.
     */
    public function index(): ?int
    {
        $index = array_search($this, self::linear(), true);

        return $index === false ? null : $index;
    }

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Erstellt',
            self::Sent => 'Versendet',
            self::Interview1 => '1. Gespräch',
            self::Interview2 => '2. Gespräch',
            self::Interview3 => '3. Gespräch',
            self::Rejected => 'Abgesagt',
        };
    }
}
