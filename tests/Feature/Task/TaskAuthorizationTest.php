<?php

declare(strict_types=1);

namespace Tests\Feature\Task;

use App\Enums\UserRole;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TaskAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_cannot_create_a_task(): void
    {
        $member = User::factory()->create();

        $response = $this->actingAs($member, 'sanctum')->postJson('/api/v1/tasks', [
            'title' => 'Should not be allowed',
        ]);

        $response->assertForbidden();
    }

    public function test_member_cannot_delete_a_task_created_by_someone_else(): void
    {
        $manager = User::factory()->manager()->create();
        $member = User::factory()->create();
        $task = Task::factory()->create(['created_by' => $manager->id]);

        $response = $this->actingAs($member, 'sanctum')->deleteJson("/api/v1/tasks/{$task->id}");

        $response->assertForbidden();
    }

    public function test_member_can_view_task_assigned_to_them(): void
    {
        $manager = User::factory()->manager()->create();
        $member = User::factory()->create();
        $task = Task::factory()->create(['created_by' => $manager->id, 'assigned_to' => $member->id]);

        $response = $this->actingAs($member, 'sanctum')->getJson("/api/v1/tasks/{$task->id}");

        $response->assertOk();
    }

    public function test_member_cannot_view_unrelated_task(): void
    {
        $manager = User::factory()->manager()->create();
        $otherMember = User::factory()->create();
        $member = User::factory()->create();
        $task = Task::factory()->create(['created_by' => $manager->id, 'assigned_to' => $otherMember->id]);

        $response = $this->actingAs($member, 'sanctum')->getJson("/api/v1/tasks/{$task->id}");

        $response->assertForbidden();
    }

    public function test_member_cannot_assign_tasks(): void
    {
        $manager = User::factory()->manager()->create();
        $member = User::factory()->create();
        $task = Task::factory()->create(['created_by' => $manager->id, 'assigned_to' => $member->id]);

        $response = $this->actingAs($member, 'sanctum')
            ->patchJson("/api/v1/tasks/{$task->id}/assign", ['assigned_to' => $member->id]);

        $response->assertForbidden();
    }

    public function test_assigned_member_can_update_their_task_status(): void
    {
        $manager = User::factory()->manager()->create();
        $member = User::factory()->create();
        $task = Task::factory()->create(['created_by' => $manager->id, 'assigned_to' => $member->id]);

        $response = $this->actingAs($member, 'sanctum')->putJson("/api/v1/tasks/{$task->id}", [
            'title' => $task->title,
            'priority' => $task->priority->value,
            'status' => 'in_progress',
        ]);

        $response->assertOk()->assertJsonPath('data.status', 'in_progress');
    }

    public function test_admin_can_view_any_task(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $manager = User::factory()->manager()->create();
        $task = Task::factory()->create(['created_by' => $manager->id]);

        $response = $this->actingAs($admin, 'sanctum')->getJson("/api/v1/tasks/{$task->id}");

        $response->assertOk();
    }
}
