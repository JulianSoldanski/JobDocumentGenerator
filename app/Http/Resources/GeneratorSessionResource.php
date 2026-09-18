<?php

namespace App\Http\Resources;

use App\Models\Document;
use App\Models\GeneratorSession;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin GeneratorSession
 */
class GeneratorSessionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'job_url' => $this->job_url ?? '',
            'job_posting' => $this->job_posting ?? '',
            'company' => $this->company,
            'position' => $this->position,
            'contact_person' => $this->contact_person,
            'city' => $this->city,
            'company_address' => $this->company_address ?? '',
            'language' => $this->language->value,
            'layout' => $this->layout->value,
            'scope' => $this->scope->value,
            'notes' => $this->notes ?? '',
            'summary' => $this->summary,
            'title' => $this->title(),
            'timer_started_at' => $this->timer_started_at?->toIso8601String(),
            'application_id' => $this->application_id,
            // Je Typ die aktuelle Fassung — die Vorschau lädt sie über ihre ID.
            // Nur die Dokumente der aktuellen Stelle — frühere Bewerbungen
            // behalten ihre eigenen.
            'documents' => $this->documents()->where('application_id', $this->application_id)->get()->mapWithKeys(
                fn (Document $document): array => [
                    $document->type->value => [
                        'id' => $document->id,
                        'version' => $document->version,
                        'edited' => (bool) ($document->content['edited'] ?? false),
                    ],
                ]
            )->all(),
        ];
    }
}
