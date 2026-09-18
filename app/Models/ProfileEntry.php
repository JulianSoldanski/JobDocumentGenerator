<?php

namespace App\Models;

use App\Enums\Language;
use App\Enums\ProfileSection;
use App\Models\Concerns\HasTranslations;
use Database\Factories\ProfileEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Eine Station, eine Ausbildung, ein Skill oder eine Sprache.
 *
 * @property int $id
 * @property int $user_id
 * @property ProfileSection $section
 * @property string $organization
 * @property string|null $start_month
 * @property string|null $end_month
 * @property bool $is_current
 * @property bool $is_visible
 * @property bool $is_subtle
 * @property int $position
 * @property array<string, array<string, string|array<int, string>>> $translations
 * @property string|null $legacy_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 */
#[Fillable([
    'section', 'organization', 'start_month', 'end_month',
    'is_current', 'is_visible', 'is_subtle', 'position', 'translations', 'legacy_id',
])]
class ProfileEntry extends Model
{
    /** @use HasFactory<ProfileEntryFactory> */
    use HasFactory, HasTranslations;

    /** @var array<string, mixed> */
    protected $attributes = [
        'translations' => '{}',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'section' => ProfileSection::class,
            'is_current' => 'boolean',
            'is_visible' => 'boolean',
            'is_subtle' => 'boolean',
            'translations' => 'array',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return array<int, string> */
    public function translatableTextFields(): array
    {
        return $this->sectionOrDefault()->textFields();
    }

    /** @return array<int, string> */
    public function translatableListFields(): array
    {
        return $this->sectionOrDefault()->listFields();
    }

    public function headlineField(): string
    {
        return $this->sectionOrDefault()->headlineField();
    }

    /**
     * Die Überschrift des Eintrags in der gewünschten Sprache.
     */
    public function headline(Language $language): string
    {
        return $this->localizedText($language, $this->headlineField());
    }

    /**
     * @param  Builder<ProfileEntry>  $query
     */
    public function scopeSection(Builder $query, ProfileSection $section): void
    {
        $query->where('section', $section);
    }

    /**
     * @param  Builder<ProfileEntry>  $query
     */
    public function scopeVisible(Builder $query): void
    {
        $query->where('is_visible', true);
    }

    /**
     * Die Reihenfolge, in der Stationen auf dem Lebenslauf stehen: laufende
     * zuerst, dann nach Enddatum absteigend.
     */
    public function sortKey(): string
    {
        if ($this->is_current) {
            return '9999-99';
        }

        return $this->end_month ?: ($this->start_month ?: '0000-00');
    }

    private function sectionOrDefault(): ProfileSection
    {
        // Beim Anlegen kann der Abschnitt noch fehlen, etwa wenn die
        // Übersetzungen vor ihm zugewiesen werden.
        $section = $this->getAttribute('section');

        return $section instanceof ProfileSection ? $section : ProfileSection::Experience;
    }
}
