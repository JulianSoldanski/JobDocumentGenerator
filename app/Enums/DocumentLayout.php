<?php

namespace App\Enums;

enum DocumentLayout: string
{
    case Modern = 'modern';
    case Sidebar = 'sidebar';
    case Classic = 'classic';

    public function label(): string
    {
        return match ($this) {
            self::Modern => 'Modern',
            self::Sidebar => 'Sidebar',
            self::Classic => 'Classic',
        };
    }
}
