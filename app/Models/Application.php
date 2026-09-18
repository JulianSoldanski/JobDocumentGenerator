<?php

namespace App\Models;

use App\Enums\ApplicationStage;
use Carbon\CarbonInterface;
use Database\Factories\ApplicationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Eine Bewerbung: das, was der Tracker verfolgt.
 *
 * @property int $id
 * @property int $user_id
 * @property string $company
 * @property string $position
 * @property string $company_key
 * @property string $position_key
 * @property string|null $job_url
 * @property string|null $job_posting
 * @property Carbon|null $applied_on
 * @property string|null $feedback
 * @property int $research_seconds
 * @property ApplicationStage $current_stage
 * @property string|null $legacy_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Collection<int, StageEvent> $stageEvents
 * @property-read Collection<int, Document> $documents
 */
#[Fillable([
    'company', 'position', 'job_url', 'job_posting',
    'applied_on', 'feedback', 'research_seconds', 'legacy_id',
])]
class Application extends Model
{
    /** @use HasFactory<ApplicationFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        // Der Vergleichsschlüssel folgt immer den Namen — beim Generieren wie
        // beim manuellen Anlegen.
        static::saving(function (Application $application): void {
            $application->company_key = self::key($application->company);
            $application->position_key = self::key($application->position);
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'applied_on' => 'date',
            'current_stage' => ApplicationStage::class,
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<StageEvent, $this> */
    public function stageEvents(): HasMany
    {
        return $this->hasMany(StageEvent::class)->orderBy('occurred_at')->orderBy('id');
    }

    /** @return HasMany<Document, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
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

    /**
     * Wechselt die Stufe — als neues Ereignis, nie durch Überschreiben.
     *
     * Der Wechsel auf „Versendet" setzt das Bewerbungsdatum, sofern noch
     * keines eingetragen ist.
     */
    public function moveTo(ApplicationStage $stage, ?CarbonInterface $at = null): void
    {
        $at ??= now();

        $this->stageEvents()->create(['stage' => $stage, 'occurred_at' => $at]);

        $changes = ['current_stage' => $stage];

        if ($stage === ApplicationStage::Sent && $this->applied_on === null) {
            $changes['applied_on'] = $at->toDateString();
        }

        $this->forceFill($changes)->save();
        $this->unsetRelation('stageEvents');
    }

    /**
     * Nach einer Absage zurück auf die Stufe, auf der die Bewerbung zuletzt
     * stand — der zurückgelegte Weg geht nicht verloren.
     */
    public function reactivate(?CarbonInterface $at = null): void
    {
        $last = $this->stageEvents
            ->filter(fn (StageEvent $event): bool => $event->stage->isLinear())
            ->last();

        $this->moveTo($last->stage ?? ApplicationStage::Created, $at);
    }

    /**
     * Der Schlüssel, über den beim Generieren dedupliziert wird.
     */
    public static function key(?string $value): string
    {
        return mb_strtolower(trim((string) $value));
    }

    public function isRejected(): bool
    {
        return $this->current_stage === ApplicationStage::Rejected;
    }

    /**
     * Die weiteste Stufe, die diese Bewerbung erreicht hat — auch dann, wenn
     * danach abgesagt oder zurückgestuft wurde. So bleibt sichtbar, wie weit
     * es ging.
     */
    public function highestStageReached(): ?ApplicationStage
    {
        $highest = null;

        foreach ($this->stageEvents as $event) {
            $index = $event->stage->index();
            if ($index !== null && ($highest === null || $index > $highest->index())) {
                $highest = $event->stage;
            }
        }

        return $highest;
    }

    /**
     * Der Zeitpunkt, seit dem die Bewerbung in der aktuellen Stufe liegt.
     */
    public function currentStageSince(): ?CarbonInterface
    {
        return $this->stageEvents->last()?->occurred_at;
    }
}
