<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(6),
            'description' => fake()->optional()->paragraph(),
            'status' => TaskStatus::Pending,
            'priority' => fake()->randomElement(TaskPriority::cases()),
            'due_date' => fake()->optional(0.7)->dateTimeBetween('now', '+30 days'),
            'created_by' => User::factory(),
            'assigned_to' => null,
        ];
    }

    public function withStatus(TaskStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
            'completed_at' => $status === TaskStatus::Completed ? now() : null,
        ]);
    }

    public function assignedTo(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'assigned_to' => $user->id,
        ]);
    }
}
