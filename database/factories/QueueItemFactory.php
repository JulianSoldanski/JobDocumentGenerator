<?php

namespace Database\Factories;

use App\Enums\QueueItemStatus;
use App\Models\QueueItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QueueItem>
 */
class QueueItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'url' => 'https://example.com/jobs/'.fake()->unique()->numberBetween(1000, 9999),
            'title' => 'Junior Product Engineer (m/w/d)',
            'note' => '',
            'status' => QueueItemStatus::Open,
        ];
    }
}
