<?php

declare(strict_types=1);

namespace Tests\Feature\ActivityLog;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_task_records_an_activity_log_entry(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager, 'sanctum')->postJson('/api/v1/tasks', [
            'title' => 'Track this task',
        ])->assertCreated();

        $task = Task::query()->where('title', 'Track this task')->firstOrFail();

        $this->assertDatabaseHas('activity_logs', [
            'subject_type' => Task::class,
            'subject_id' => $task->id,
            'event' => 'created',
            'causer_id' => $manager->id,
        ]);
    }

    public function test_status_change_is_logged_with_from_and_to_values(): void
    {
        $manager = User::factory()->manager()->create();
        $task = Task::factory()->create(['created_by' => $manager->id]);

        $this->actingAs($manager, 'sanctum')->putJson("/api/v1/tasks/{$task->id}", [
            'title' => $task->title,
            'priority' => $task->priority->value,
            'status' => 'completed',
        ])->assertOk();

        $this->assertDatabaseHas('activity_logs', [
            'subject_type' => Task::class,
            'subject_id' => $task->id,
            'event' => 'status_changed',
        ]);
    }

    public function test_activity_logs_can_be_listed_for_a_task(): void
    {
        $manager = User::factory()->manager()->create();
        $task = Task::factory()->create(['created_by' => $manager->id]);

        $response = $this->actingAs($manager, 'sanctum')->getJson("/api/v1/tasks/{$task->id}/activity-logs");

        $response->assertOk()->assertJsonStructure(['data', 'meta']);
    }

    public function test_unrelated_member_cannot_view_activity_logs(): void
    {
        $manager = User::factory()->manager()->create();
        $member = User::factory()->create();
        $task = Task::factory()->create(['created_by' => $manager->id]);

        $response = $this->actingAs($member, 'sanctum')->getJson("/api/v1/tasks/{$task->id}/activity-logs");

        $response->assertForbidden();
    }
}
