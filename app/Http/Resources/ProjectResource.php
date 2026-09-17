<?php

namespace App\Http\Resources;

use App\Enums\Language;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Project
 */
class ProjectResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'position' => $this->position,
            'is_visible' => $this->is_visible,
            'in_project_list' => $this->in_project_list,
            'link' => $this->link,
            'grade' => $this->grade,
            'tags' => $this->tags,
            'client' => $this->client,
            'period' => $this->period,
            'team_size' => $this->team_size,
            'technologies' => $this->technologies,
            'translations' => $this->translations,
            'headline' => $this->localizedText(Language::German, 'title'),
            'has_long_form' => $this->hasLongForm(),
            'missing_languages' => array_map(
                fn (Language $language): string => $language->value,
                $this->missingLanguages()
            ),
        ];
    }
}
