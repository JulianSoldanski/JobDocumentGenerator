<?php

namespace App\Enums;

enum QueueItemStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Done = 'done';
    case Skipped = 'skipped';
    case Failed = 'failed';

    public function isOpen(): bool
    {
        return $this === self::Open;
    }

    public function label(): string
    {
        return match ($this) {
            self::Open => 'offen',
            self::InProgress => 'in Arbeit',
            self::Done => 'erledigt',
            self::Skipped => 'übersprungen',
            self::Failed => 'fehlgeschlagen',
        };
    }
}
