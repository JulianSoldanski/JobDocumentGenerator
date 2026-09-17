<?php

namespace App\Models;

use App\Enums\DocumentLayout;
use App\Enums\GenerationScope;
use App\Enums\Language;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Der Arbeitsplatz für genau eine Stelle.
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $queue_item_id
 * @property int|null $application_id
 * @property string|null $job_url
 * @property string|null $job_posting
 * @property string $company
 * @property string $position
 * @property string $contact_person
 * @property string $city
 * @property string|null $company_address
 * @property Language $language
 * @property DocumentLayout $layout
 * @property GenerationScope $scope
 * @property string|null $notes
 * @property array<string, mixed>|null $summary
 * @property Carbon|null $timer_started_at
 * @property int $pending_research_seconds
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read QueueItem|null $queueItem
 * @property-read Application|null $application
 */
#[Fillable([
    'queue_item_id', 'application_id', 'job_url', 'job_posting', 'company', 'position',
    'contact_person', 'city', 'company_address', 'language', 'layout', 'scope', 'notes',
])]
class GeneratorSession extends Model
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'language' => Language::class,
            'layout' => DocumentLayout::class,
            'scope' => GenerationScope::class,
            'summary' => 'array',
            'timer_started_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<QueueItem, $this> */
    public function queueItem(): BelongsTo
    {
        return $this->belongsTo(QueueItem::class);
    }

    /** @return BelongsTo<Application, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /** @return HasMany<Document, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /** @return HasMany<AiTask, $this> */
    public function aiTasks(): HasMany
    {
        return $this->hasMany(AiTask::class, 'subject_id')->where('subject_type', self::class);
    }

    /**
     * Eine Stelle gilt als identifiziert, sobald eine Anzeige vorliegt — ab da
     * läuft die Uhr.
     */
    public function hasPosting(): bool
    {
        return trim((string) $this->job_posting) !== '';
    }

    public function title(): string
    {
        $parts = array_filter([trim($this->company), trim($this->position)]);

        return $parts === [] ? 'Ohne Titel' : implode(' — ', $parts);
    }
}
