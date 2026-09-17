<?php

namespace App\Models;

use App\Enums\AiTaskStatus;
use App\Enums\AiTaskType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * Ein einzelner KI-Aufruf, der im Hintergrund läuft.
 *
 * @property string $id
 * @property int $user_id
 * @property AiTaskType $type
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property AiTaskStatus $status
 * @property array<string, mixed> $input
 * @property array<string, mixed>|null $result
 * @property string|null $error
 * @property Carbon|null $started_at
 * @property Carbon|null $finished_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 */
#[Fillable(['type', 'input'])]
class AiTask extends Model
{
    use HasUuids;

    /** @var array<string, mixed> */
    protected $attributes = [
        'input' => '{}',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => AiTaskType::class,
            'status' => AiTaskStatus::class,
            'input' => 'array',
            'result' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return MorphTo<Model, $this> */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function markRunning(): void
    {
        $this->forceFill(['status' => AiTaskStatus::Running, 'started_at' => now()])->save();
    }

    /**
     * @param  array<string, mixed>  $result
     */
    public function markSucceeded(array $result): void
    {
        $this->forceFill([
            'status' => AiTaskStatus::Succeeded,
            'result' => $result,
            'error' => null,
            'finished_at' => now(),
        ])->save();
    }

    public function markFailed(string $error): void
    {
        $this->forceFill([
            'status' => AiTaskStatus::Failed,
            'error' => $error,
            'finished_at' => now(),
        ])->save();
    }
}
