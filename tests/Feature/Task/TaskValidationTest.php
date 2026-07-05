<?php

declare(strict_types=1);

namespace Tests\Feature\Task;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TaskValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_title_is_required(): void
    {
        $manager = User::factory()->manager()->create();

        $response = $this->actingAs($manager, 'sanctum')->postJson('/api/v1/tasks', []);

        $response->assertUnprocessable()->assertJsonValidationErrors('title');
    }

    public function test_priority_must_be_a_valid_enum_value(): void
    {
        $manager = User::factory()->manager()->create();

        $response = $this->actingAs($manager, 'sanctum')->postJson('/api/v1/tasks', [
            'title' => 'Task with bad priority',
            'priority' => 'not-a-real-priority',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('priority');
    }

    public function test_due_date_cannot_be_in_the_past_on_creation(): void
    {
        $manager = User::factory()->manager()->create();

        $response = $this->actingAs($manager, 'sanctum')->postJson('/api/v1/tasks', [
            'title' => 'Overdue from the start',
            'due_date' => now()->subDay()->toDateString(),
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('due_date');
    }

    public function test_assigned_to_must_reference_an_existing_user(): void
    {
        $manager = User::factory()->manager()->create();

        $response = $this->actingAs($manager, 'sanctum')->postJson('/api/v1/tasks', [
            'title' => 'Assigned to nobody',
            'assigned_to' => 999999,
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('assigned_to');
    }

    public function test_index_rejects_invalid_sort_direction(): void
    {
        $manager = User::factory()->manager()->create();

        $response = $this->actingAs($manager, 'sanctum')->getJson('/api/v1/tasks?sort_direction=sideways');

        $response->assertUnprocessable()->assertJsonValidationErrors('sort_direction');
    }

    public function test_error_response_has_consistent_shape(): void
    {
        $manager = User::factory()->manager()->create();

        $response = $this->actingAs($manager, 'sanctum')->postJson('/api/v1/tasks', []);

        $response->assertUnprocessable()->assertJsonStructure(['message', 'errors']);
    }
}
