<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $full_name
 * @property string $street
 * @property string $postal_code
 * @property string $city
 * @property string $phone
 * @property string $email
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 */
#[Fillable(['full_name', 'street', 'postal_code', 'city', 'phone', 'email'])]
class ContactDetail extends Model
{
    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Die Anschrift, wie sie im Briefkopf steht.
     *
     * @return array<int, string>
     */
    public function addressLines(): array
    {
        return array_values(array_filter([
            trim($this->street),
            trim($this->postal_code.' '.$this->city),
        ], fn (string $line): bool => $line !== ''));
    }

    /**
     * Die Anschrift einzeilig, etwa für die Kopfzeile des Lebenslaufs.
     */
    public function addressLine(): string
    {
        return implode(' · ', $this->addressLines());
    }
}
