<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ActivityEvent;
use App\Models\ActivityLog;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event' => ActivityEvent::Created,
            'description' => fake()->sentence(),
            'subject_type' => Task::class,
            'subject_id' => Task::factory(),
            'causer_id' => null,
            'properties' => [],
        ];
    }
}
