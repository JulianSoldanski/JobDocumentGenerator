<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'position' => 0,
            'is_visible' => true,
            'in_project_list' => true,
            'link' => null,
            'grade' => null,
            'tags' => ['react', 'typescript'],
            'client' => '',
            'period' => '',
            'team_size' => '',
            'technologies' => [],
            'translations' => [
                'de' => [
                    'title' => 'Internes Analytics-Dashboard',
                    'summary' => 'Dashboard zur Visualisierung von Nutzungsdaten, von der Skizze bis zum Rollout.',
                ],
                'en' => [
                    'title' => 'Internal analytics dashboard',
                    'summary' => 'Dashboard visualising usage data, from sketch to rollout.',
                ],
            ],
        ];
    }

    public function withLongForm(): self
    {
        return $this->state(fn (): array => [
            'client' => 'MusterTech GmbH',
            'period' => '03/2024 – 07/2024',
            'team_size' => '4 Personen',
            'technologies' => ['React', 'PostgreSQL'],
            'translations' => [
                'de' => [
                    'title' => 'Internes Analytics-Dashboard',
                    'summary' => 'Dashboard zur Visualisierung von Nutzungsdaten.',
                    'role' => 'Fullstack-Entwickler',
                    'situation' => 'Auswertungen liefen verstreut über Excel-Dateien.',
                    'contributions' => ['Datenmodell entworfen', 'Frontend umgesetzt'],
                    'result' => 'Grundlage der wöchentlichen Produkt-Reviews.',
                ],
                'en' => [
                    'title' => 'Internal analytics dashboard',
                    'summary' => 'Dashboard visualising usage data.',
                    'role' => 'Fullstack developer',
                    'situation' => 'Reporting was scattered across spreadsheets.',
                    'contributions' => ['Designed the data model', 'Built the frontend'],
                    'result' => 'Now the basis for weekly product reviews.',
                ],
            ],
        ]);
    }
}
