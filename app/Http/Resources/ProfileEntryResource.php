<?php

namespace App\Http\Resources;

use App\Enums\Language;
use App\Models\ProfileEntry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProfileEntry
 */
class ProfileEntryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'section' => $this->section->value,
            'organization' => $this->organization,
            'start_month' => $this->start_month,
            'end_month' => $this->end_month,
            'is_current' => $this->is_current,
            'is_visible' => $this->is_visible,
            'is_subtle' => $this->is_subtle,
            'position' => $this->position,
            'translations' => $this->translations,
            'headline' => $this->headline(Language::German),
            // Fehlende Sprachen werden im Editor sichtbar markiert — hier steht,
            // welche das sind, nicht wie sie zu füllen wären.
            'missing_languages' => array_map(
                fn (Language $language): string => $language->value,
                $this->missingLanguages()
            ),
        ];
    }
}
