<?php

namespace Database\Factories;

use App\Enums\ApplicationStage;
use App\Models\Application;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $company = fake()->company();
        $position = 'Junior Product Engineer';

        return [
            'user_id' => User::factory(),
            'company' => $company,
            'position' => $position,
            'company_key' => Application::key($company),
            'position_key' => Application::key($position),
            'current_stage' => ApplicationStage::Created,
            'research_seconds' => 0,
        ];
    }
}
