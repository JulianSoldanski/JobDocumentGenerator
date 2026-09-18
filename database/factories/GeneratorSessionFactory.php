<?php

namespace Database\Factories;

use App\Enums\DocumentLayout;
use App\Enums\GenerationScope;
use App\Enums\Language;
use App\Models\GeneratorSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GeneratorSession>
 */
class GeneratorSessionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'job_url' => null,
            'job_posting' => null,
            'company' => '',
            'position' => '',
            'contact_person' => '',
            'city' => '',
            'company_address' => null,
            'language' => Language::German,
            'layout' => DocumentLayout::Modern,
            'scope' => GenerationScope::Both,
            'notes' => null,
            'summary' => null,
        ];
    }

    public function withPosting(?string $posting = null): self
    {
        return $this->state(fn (): array => [
            'job_posting' => $posting ?? <<<'TEXT'
                MusterTech GmbH sucht eine Fullstack-Entwicklerin (m/w/d)
                Standort: Hamburg
                Ansprechpartnerin: Frau Dr. Meyer
                Musterstraße 1, 20095 Hamburg
                Wir bauen Software für die Logistikbranche.
                TEXT,
        ]);
    }
}
