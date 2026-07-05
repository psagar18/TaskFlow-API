<?php

declare(strict_types=1);

namespace Tests\Feature\Task;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TaskFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_tasks_can_be_filtered_by_status(): void
    {
        $manager = User::factory()->manager()->create();
        Task::factory()->withStatus(TaskStatus::Completed)->count(2)->create(['created_by' => $manager->id]);
        Task::factory()->withStatus(TaskStatus::Pending)->count(3)->create(['created_by' => $manager->id]);

        $response = $this->actingAs($manager, 'sanctum')->getJson('/api/v1/tasks?status=completed');

        $response->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_tasks_can_be_filtered_by_priority(): void
    {
        $manager = User::factory()->manager()->create();
        Task::factory()->create(['created_by' => $manager->id, 'priority' => TaskPriority::Urgent]);
        Task::factory()->create(['created_by' => $manager->id, 'priority' => TaskPriority::Low]);

        $response = $this->actingAs($manager, 'sanctum')->getJson('/api/v1/tasks?priority=urgent');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_tasks_can_be_searched_by_title(): void
    {
        $manager = User::factory()->manager()->create();
        Task::factory()->create(['created_by' => $manager->id, 'title' => 'Fix the login bug']);
        Task::factory()->create(['created_by' => $manager->id, 'title' => 'Unrelated task']);

        $response = $this->actingAs($manager, 'sanctum')->getJson('/api/v1/tasks?search=login');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Fix the login bug');
    }

    public function test_tasks_can_be_filtered_by_assignee(): void
    {
        $manager = User::factory()->manager()->create();
        $member = User::factory()->create();
        Task::factory()->assignedTo($member)->create(['created_by' => $manager->id]);
        Task::factory()->create(['created_by' => $manager->id]);

        $response = $this->actingAs($manager, 'sanctum')->getJson("/api/v1/tasks?assigned_to={$member->id}");

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_tasks_can_be_sorted_by_due_date_ascending(): void
    {
        $manager = User::factory()->manager()->create();
        $later = Task::factory()->create(['created_by' => $manager->id, 'due_date' => now()->addDays(10)]);
        $sooner = Task::factory()->create(['created_by' => $manager->id, 'due_date' => now()->addDay()]);

        $response = $this->actingAs($manager, 'sanctum')
            ->getJson('/api/v1/tasks?sort_by=due_date&sort_direction=asc');

        $response->assertOk()->assertJsonPath('data.0.id', $sooner->id);
    }
}
