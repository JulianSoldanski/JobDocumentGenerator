<?php

namespace App\Enums;

enum AiTaskStatus: string
{
    case Queued = 'queued';
    case Running = 'running';
    case Succeeded = 'succeeded';
    case Failed = 'failed';

    public function isFinished(): bool
    {
        return $this === self::Succeeded || $this === self::Failed;
    }
}
