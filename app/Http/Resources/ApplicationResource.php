<?php

namespace App\Http\Resources;

use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Application
 */
class ApplicationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company' => $this->company,
            'position' => $this->position,
            'job_url' => $this->job_url,
            'stage' => $this->current_stage->value,
            'stage_since' => $this->currentStageSince()?->toIso8601String(),
            // Bei einer Absage sieht man so, wie weit es ging.
            'highest_stage' => $this->highestStageReached()?->value,
            'applied_on' => $this->applied_on?->toDateString(),
            'research_seconds' => $this->research_seconds,
            'feedback' => $this->feedback ?? '',
        ];
    }
}
