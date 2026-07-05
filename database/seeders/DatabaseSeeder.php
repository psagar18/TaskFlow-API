<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $admin = User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@taskflow.test',
        ]);

        $manager = User::factory()->manager()->create([
            'name' => 'Manager User',
            'email' => 'manager@taskflow.test',
        ]);

        $members = User::factory()->count(5)->create();

        Task::factory()
            ->count(20)
            ->recycle($members)
            ->create(['created_by' => $manager->id])
            ->each(function (Task $task) use ($members): void {
                $task->update([
                    'assigned_to' => $members->random()->id,
                    'status' => fake()->randomElement(TaskStatus::cases()),
                ]);
            });

        $this->command?->info("Seeded admin: {$admin->email} / manager: {$manager->email} (password: 'password')");
    }
}
