<?php

namespace App\Models;

use App\Enums\DocumentLayout;
use App\Enums\DocumentType;
use App\Enums\GenerationScope;
use App\Enums\Language;
use Database\Factories\GeneratorSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
    /** @use HasFactory<GeneratorSessionFactory> */
    use HasFactory;

    /**
     * Dieselben Vorgaben wie in der Migration — eine frisch angelegte Sitzung
     * hat ihre Einstellungen damit schon vor dem ersten Lesen aus der
     * Datenbank.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'company' => '',
        'position' => '',
        'contact_person' => '',
        'city' => '',
        'language' => 'de',
        'layout' => 'modern',
        'scope' => 'both',
        'pending_research_seconds' => 0,
    ];

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

    /**
     * Die Uhr läuft, sobald eine Stelle identifiziert ist — also ab der ersten
     * Anzeige. Sie läuft auf dem Server, damit ein Neuladen des Tabs die
     * Recherchezeit nicht zurücksetzt.
     */
    public function startTimer(): void
    {
        if ($this->hasPosting() && $this->timer_started_at === null) {
            $this->forceFill(['timer_started_at' => now()]);
        }
    }

    /**
     * Beim Generieren wird die verstrichene Zeit gutgeschrieben. Bis es eine
     * Bewerbung gibt, sammelt sie sich hier; danach läuft die Uhr neu an,
     * damit ein zweites Generieren nur die Zeit seither zählt.
     */
    public function bankResearchTime(): void
    {
        if ($this->timer_started_at === null) {
            return;
        }

        // Ein tagelang offener Tab soll keine absurde Recherchezeit ergeben.
        $elapsed = min(
            (int) $this->timer_started_at->diffInSeconds(now(), true),
            (int) config('cvcreater.research_timer.max_segment_seconds'),
        );

        $this->forceFill([
            'pending_research_seconds' => $this->pending_research_seconds + $elapsed,
            'timer_started_at' => now(),
        ]);
    }

    /**
     * Legt das Dokument dieses Typs an oder ersetzt seinen Inhalt. Je Sitzung
     * gibt es eines pro Typ; ein erneutes Generieren zählt die Fassung hoch.
     *
     * @param  array<string, mixed>  $content
     */
    public function storeDocument(DocumentType $type, array $content): Document
    {
        $document = $this->documents()->firstOrNew(['type' => $type->value]);

        $document->forceFill([
            'user_id' => $this->user_id,
            'language' => $this->language,
            // Nur der Lebenslauf hat Layouts; das Anschreiben hat genau eines.
            'layout' => $type === DocumentType::Cv ? $this->layout : null,
            'content' => $content,
            'version' => $document->exists ? $document->version + 1 : 1,
        ]);

        $document->invalidateRendering();
        $document->save();

        return $document;
    }

    public function title(): string
    {
        $parts = array_filter([trim($this->company), trim($this->position)]);

        return $parts === [] ? 'Ohne Titel' : implode(' — ', $parts);
    }
}
