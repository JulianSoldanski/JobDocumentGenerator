<?php

namespace App\Models;

use App\Enums\QueueItemStatus;
use Database\Factories\QueueItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Eine gemerkte Stellenanzeige.
 *
 * @property int $id
 * @property int $user_id
 * @property string $url
 * @property string $title
 * @property string $note
 * @property QueueItemStatus $status
 * @property Carbon|null $processed_at
 * @property int|null $application_id
 * @property string|null $legacy_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Application|null $application
 */
#[Fillable(['url', 'title', 'note', 'status', 'processed_at', 'application_id', 'legacy_id'])]
class QueueItem extends Model
{
    /** @use HasFactory<QueueItemFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => QueueItemStatus::class,
            'processed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Application, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * Offene Einträge zuerst, davon die ältesten oben: die Queue wird von
     * hinten abgearbeitet. Alles andere liegt darunter im Archiv, neueste
     * zuerst.
     *
     * @param  Builder<QueueItem>  $query
     */
    public function scopeWorkOrder(Builder $query): void
    {
        $query
            ->orderByRaw("CASE WHEN status = 'open' THEN 0 ELSE 1 END")
            ->orderByRaw("CASE WHEN status = 'open' THEN created_at END ASC")
            ->orderBy('created_at', 'desc');
    }

    /**
     * @param  Builder<QueueItem>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->where('status', QueueItemStatus::Open);
    }
}
