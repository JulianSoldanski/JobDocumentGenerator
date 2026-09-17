<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Beispiel-Anschreiben und die daraus destillierten Stilregeln.
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $example
 * @property array<int, string> $rules
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 */
#[Fillable(['example', 'rules'])]
class WritingStyle extends Model
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['rules' => 'array'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hasRules(): bool
    {
        return $this->rules !== [];
    }
}
