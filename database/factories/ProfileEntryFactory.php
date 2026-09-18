<?php

namespace Database\Factories;

use App\Enums\ProfileSection;
use App\Models\ProfileEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProfileEntry>
 */
class ProfileEntryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'section' => ProfileSection::Experience,
            'organization' => fake()->company(),
            'start_month' => '2024-01',
            'end_month' => '2025-06',
            'is_current' => false,
            'is_visible' => true,
            'is_subtle' => false,
            'position' => 0,
            'translations' => [
                'de' => [
                    'title' => 'Werkstudent Softwareentwicklung',
                    'location' => 'Berlin',
                    'bullets' => ['Frontend-Features umgesetzt'],
                ],
                'en' => [
                    'title' => 'Working Student, Software Engineering',
                    'location' => 'Berlin, Germany',
                    'bullets' => ['Shipped frontend features'],
                ],
            ],
        ];
    }

    public function education(): self
    {
        return $this->state(fn (): array => [
            'section' => ProfileSection::Education,
            'organization' => fake()->company().' Universität',
            'translations' => [
                'de' => ['degree' => 'B.Sc. Wirtschaftsinformatik', 'location' => 'Berlin', 'details' => ['Note 1,7']],
                'en' => ['degree' => 'B.Sc. Information Systems', 'location' => 'Berlin', 'details' => ['Grade 1.7']],
            ],
        ]);
    }

    /**
     * Ein Eintrag, der nur auf Deutsch geschrieben ist — der Fall, den die
     * Oberfläche mit "EN fehlt" kennzeichnet.
     */
    public function germanOnly(): self
    {
        return $this->state(fn (): array => [
            'translations' => [
                'de' => ['title' => 'Werkstudent Datenanalyse', 'location' => 'Hamburg', 'bullets' => ['Auswertungen automatisiert']],
                'en' => [],
            ],
        ]);
    }

    public function skill(ProfileSection $section = ProfileSection::HardSkill, string $de = 'Python', string $en = 'Python'): self
    {
        return $this->state(fn (): array => [
            'section' => $section,
            'organization' => '',
            'start_month' => null,
            'end_month' => null,
            'translations' => ['de' => ['name' => $de], 'en' => ['name' => $en]],
        ]);
    }
}
