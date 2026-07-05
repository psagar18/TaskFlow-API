<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTOs\TaskData;
use App\Enums\TaskPriority;
use App\Models\Task;
use App\Repositories\Contracts\TaskRepositoryInterface;
use App\Services\TaskService;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class TaskServiceTest extends TestCase
{
    #[Test]
    public function it_creates_a_task_with_the_authenticated_user_as_creator(): void
    {
        $repository = Mockery::mock(TaskRepositoryInterface::class);
        $repository->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn (array $attributes): bool => $attributes['created_by'] === 42
                && $attributes['title'] === 'Write tests'
            ))
            ->andReturn(new Task(['title' => 'Write tests']));

        $service = new TaskService($repository);

        $data = new TaskData(
            title: 'Write tests',
            description: null,
            priority: TaskPriority::Medium,
            dueDate: null,
            assignedTo: null,
        );

        $task = $service->create($data, createdBy: 42);

        $this->assertSame('Write tests', $task->title);
    }

    #[Test]
    public function it_delegates_assignment_to_the_repository(): void
    {
        $task = new Task(['title' => 'Assign me']);

        $repository = Mockery::mock(TaskRepositoryInterface::class);
        $repository->shouldReceive('update')
            ->once()
            ->with($task, ['assigned_to' => 7])
            ->andReturn($task);

        $service = new TaskService($repository);

        $service->assign($task, 7);

        $this->assertTrue(true);
    }

    #[Test]
    public function it_delegates_deletion_to_the_repository(): void
    {
        $task = new Task(['title' => 'Delete me']);

        $repository = Mockery::mock(TaskRepositoryInterface::class);
        $repository->shouldReceive('delete')->once()->with($task)->andReturn(true);

        $service = new TaskService($repository);

        $service->delete($task);

        $this->assertTrue(true);
    }
}
