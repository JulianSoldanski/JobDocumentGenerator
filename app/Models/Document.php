<?php

namespace App\Models;

use App\Enums\DocumentLayout;
use App\Enums\DocumentType;
use App\Enums\Language;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Ein erzeugtes Dokument mit seinem Inhalt.
 *
 * Der Inhalt ist der Snapshot dieser Fassung: Monate später ist damit noch
 * nachvollziehbar, was tatsächlich verschickt wurde.
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $application_id
 * @property int|null $generator_session_id
 * @property DocumentType $type
 * @property Language $language
 * @property DocumentLayout|null $layout
 * @property array<string, mixed> $content
 * @property int $version
 * @property string|null $html_path
 * @property string|null $pdf_path
 * @property Carbon|null $rendered_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Application|null $application
 */
#[Fillable(['type', 'language', 'layout', 'content', 'version'])]
class Document extends Model
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => DocumentType::class,
            'language' => Language::class,
            'layout' => DocumentLayout::class,
            'content' => 'array',
            'rendered_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Application, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /** @return BelongsTo<GeneratorSession, $this> */
    public function generatorSession(): BelongsTo
    {
        return $this->belongsTo(GeneratorSession::class);
    }

    /**
     * Gerenderte Dateien sind nach einer Inhaltsänderung nicht mehr gültig.
     */
    public function invalidateRendering(): void
    {
        $this->forceFill([
            'html_path' => null,
            'pdf_path' => null,
            'rendered_at' => null,
        ]);
    }
}
