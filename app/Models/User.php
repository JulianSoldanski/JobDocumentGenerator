<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $capture_token
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'capture_token', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /** @return HasOne<ContactDetail, $this> */
    public function contactDetail(): HasOne
    {
        return $this->hasOne(ContactDetail::class);
    }

    /** @return HasOne<WritingStyle, $this> */
    public function writingStyle(): HasOne
    {
        return $this->hasOne(WritingStyle::class);
    }

    /** @return HasMany<ProfileEntry, $this> */
    public function profileEntries(): HasMany
    {
        return $this->hasMany(ProfileEntry::class);
    }

    /** @return HasMany<Project, $this> */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class)->orderBy('position');
    }

    /** @return HasMany<Application, $this> */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    /** @return HasMany<QueueItem, $this> */
    public function queueItems(): HasMany
    {
        return $this->hasMany(QueueItem::class);
    }

    /** @return HasMany<GeneratorSession, $this> */
    public function generatorSessions(): HasMany
    {
        return $this->hasMany(GeneratorSession::class);
    }

    /** @return HasMany<Document, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /** @return HasMany<AiTask, $this> */
    public function aiTasks(): HasMany
    {
        return $this->hasMany(AiTask::class);
    }

    /**
     * Das Kontaktblatt, notfalls frisch angelegt — jeder Nutzer hat genau eines.
     */
    public function contact(): ContactDetail
    {
        return $this->contactDetail()->firstOrCreate([]);
    }

    /**
     * Der Schreibstil, notfalls frisch angelegt.
     */
    public function style(): WritingStyle
    {
        return $this->writingStyle()->firstOrCreate([], ['rules' => []]);
    }

    /**
     * Das Token, mit dem das Bookmarklet Stellen in die Queue schickt.
     */
    public function captureToken(): string
    {
        if (! $this->capture_token) {
            $this->regenerateCaptureToken();
        }

        return (string) $this->capture_token;
    }

    public function regenerateCaptureToken(): string
    {
        $token = Str::random(48);
        $this->forceFill(['capture_token' => $token])->save();

        return $token;
    }
}
