<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\TaskData;
use App\DTOs\TaskFilterData;
use App\Models\Task;
use App\Repositories\Contracts\TaskRepositoryInterface;
use App\Services\Contracts\TaskServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class TaskService implements TaskServiceInterface
{
    public function __construct(private TaskRepositoryInterface $tasks) {}

    public function list(TaskFilterData $filters): LengthAwarePaginator
    {
        return $this->tasks->paginate($filters);
    }

    public function find(int $id): Task
    {
        return $this->tasks->findOrFail($id);
    }

    public function create(TaskData $data, int $createdBy): Task
    {
        return $this->tasks->create([
            ...$data->toArray(),
            'created_by' => $createdBy,
        ]);
    }

    public function update(Task $task, TaskData $data): Task
    {
        return $this->tasks->update($task, $data->toArray());
    }

    public function delete(Task $task): void
    {
        $this->tasks->delete($task);
    }

    public function assign(Task $task, ?int $assigneeId): Task
    {
        return $this->tasks->update($task, ['assigned_to' => $assigneeId]);
    }
}
