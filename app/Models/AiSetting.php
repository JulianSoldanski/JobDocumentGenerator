<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Der KI-Zugang eines Nutzers.
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $provider
 * @property string|null $model
 * @property string|null $api_key
 * @property CarbonInterface|null $verified_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 */
#[Fillable(['provider', 'model', 'api_key'])]
#[Hidden(['api_key'])]
class AiSetting extends Model
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            // Verschlüsselt in der Datenbank: Personendaten und fremde
            // Zugangsdaten sollen bei einem Blick in die Tabelle nichts
            // preisgeben.
            'api_key' => 'encrypted',
            'verified_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hasKey(): bool
    {
        return trim((string) $this->api_key) !== '';
    }

    /**
     * Die letzten vier Zeichen — genug, um zwei Schlüssel auseinanderzuhalten,
     * zu wenig, um etwas damit anzufangen.
     */
    public function hint(): ?string
    {
        return $this->hasKey() ? '…'.mb_substr((string) $this->api_key, -4) : null;
    }
}
