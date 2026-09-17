<?php

namespace App\Models;

use App\Enums\ApplicationStage;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * Ein Stufenwechsel mit Zeitstempel.
 *
 * Diese Zeilen werden nur angehängt, nie geändert und nie einzeln gelöscht —
 * sie sind die Grundlage der gesamten Statistik. Das Model setzt das durch,
 * damit auch ein späterer Programmierfehler die Historie nicht verbiegt.
 *
 * @property int $id
 * @property int $application_id
 * @property ApplicationStage $stage
 * @property Carbon $occurred_at
 * @property Carbon|null $created_at
 * @property-read Application $application
 */
#[Fillable(['stage', 'occurred_at'])]
class StageEvent extends Model
{
    public const UPDATED_AT = null;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'stage' => ApplicationStage::class,
            'occurred_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (StageEvent $event): void {
            throw new LogicException('Stufen-Ereignisse werden nur angehängt, nie geändert.');
        });

        static::deleting(function (StageEvent $event): void {
            if (! $event->application()->doesntExist()) {
                throw new LogicException('Stufen-Ereignisse werden nur angehängt, nie gelöscht.');
            }
        });
    }

    /** @return BelongsTo<Application, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
