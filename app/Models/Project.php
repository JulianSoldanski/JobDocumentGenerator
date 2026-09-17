<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Ein Projekt in zwei Detailtiefen: kurz für den Lebenslauf, ausführlich für
 * die Projektliste.
 *
 * @property int $id
 * @property int $user_id
 * @property int $position
 * @property bool $is_visible
 * @property bool $in_project_list
 * @property string|null $link
 * @property string|null $grade
 * @property array<int, string> $tags
 * @property string $client
 * @property string $period
 * @property string $team_size
 * @property array<int, string> $technologies
 * @property array<string, array<string, string|array<int, string>>> $translations
 * @property string|null $legacy_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 */
#[Fillable([
    'position', 'is_visible', 'in_project_list', 'link', 'grade', 'tags',
    'client', 'period', 'team_size', 'technologies', 'translations', 'legacy_id',
])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory, HasTranslations;

    /** Sprachabhängige Textfelder eines Projekts. */
    public const TEXT_FIELDS = ['title', 'summary', 'role', 'situation', 'result'];

    /** Sprachabhängige Listenfelder eines Projekts. */
    public const LIST_FIELDS = ['contributions'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
            'in_project_list' => 'boolean',
            'tags' => 'array',
            'technologies' => 'array',
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
        return self::TEXT_FIELDS;
    }

    /** @return array<int, string> */
    public function translatableListFields(): array
    {
        return self::LIST_FIELDS;
    }

    public function headlineField(): string
    {
        return 'title';
    }

    /**
     * @param  Builder<Project>  $query
     */
    public function scopeVisible(Builder $query): void
    {
        $query->where('is_visible', true);
    }

    /**
     * True, sobald die ausführlichen Felder Inhalt haben — erst dann lohnt der
     * Eintrag in der Projektliste.
     */
    public function hasLongForm(): bool
    {
        if ($this->client !== '' || $this->period !== '' || $this->technologies !== []) {
            return true;
        }

        foreach ($this->translations as $block) {
            foreach (['role', 'situation', 'result', 'contributions'] as $field) {
                if (! empty($block[$field])) {
                    return true;
                }
            }
        }

        return false;
    }
}
