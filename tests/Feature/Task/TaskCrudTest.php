<?php

declare(strict_types=1);

namespace Tests\Feature\Task;

use App\Enums\UserRole;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TaskCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_create_a_task(): void
    {
        $manager = User::factory()->manager()->create();

        $response = $this->actingAs($manager, 'sanctum')->postJson('/api/v1/tasks', [
            'title' => 'Write documentation',
            'description' => 'Document the API',
            'priority' => 'high',
            'due_date' => now()->addWeek()->toDateString(),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'Write documentation')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.priority', 'high');

        $this->assertDatabaseHas('tasks', [
            'title' => 'Write documentation',
            'created_by' => $manager->id,
        ]);
    }

    public function test_task_creation_defaults_priority_to_medium(): void
    {
        $manager = User::factory()->manager()->create();

        $response = $this->actingAs($manager, 'sanctum')->postJson('/api/v1/tasks', [
            'title' => 'Untitled priority task',
        ]);

        $response->assertCreated()->assertJsonPath('data.priority', 'medium');
    }

    public function test_user_can_view_a_single_task(): void
    {
        $manager = User::factory()->manager()->create();
        $task = Task::factory()->create(['created_by' => $manager->id]);

        $response = $this->actingAs($manager, 'sanctum')->getJson("/api/v1/tasks/{$task->id}");

        $response->assertOk()->assertJsonPath('data.id', $task->id);
    }

    public function test_viewing_a_missing_task_returns_404(): void
    {
        $manager = User::factory()->manager()->create();

        $response = $this->actingAs($manager, 'sanctum')->getJson('/api/v1/tasks/999999');

        $response->assertNotFound();
    }

    public function test_creator_can_update_their_task(): void
    {
        $manager = User::factory()->manager()->create();
        $task = Task::factory()->create(['created_by' => $manager->id]);

        $response = $this->actingAs($manager, 'sanctum')->putJson("/api/v1/tasks/{$task->id}", [
            'title' => 'Updated title',
            'priority' => 'urgent',
            'status' => 'in_progress',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.title', 'Updated title')
            ->assertJsonPath('data.status', 'in_progress');
    }

    public function test_admin_can_delete_any_task(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $manager = User::factory()->manager()->create();
        $task = Task::factory()->create(['created_by' => $manager->id]);

        $response = $this->actingAs($admin, 'sanctum')->deleteJson("/api/v1/tasks/{$task->id}");

        $response->assertNoContent();
        $this->assertSoftDeleted('tasks', ['id' => $task->id]);
    }

    public function test_manager_can_assign_a_task_to_a_member(): void
    {
        $manager = User::factory()->manager()->create();
        $member = User::factory()->create();
        $task = Task::factory()->create(['created_by' => $manager->id]);

        $response = $this->actingAs($manager, 'sanctum')
            ->patchJson("/api/v1/tasks/{$task->id}/assign", ['assigned_to' => $member->id]);

        $response->assertOk()->assertJsonPath('data.assignee.id', $member->id);
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'assigned_to' => $member->id]);
    }

    public function test_index_returns_paginated_tasks(): void
    {
        $manager = User::factory()->manager()->create();
        Task::factory()->count(20)->create(['created_by' => $manager->id]);

        $response = $this->actingAs($manager, 'sanctum')->getJson('/api/v1/tasks?per_page=10');

        $response->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', 20);
    }
}
