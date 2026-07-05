<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\DTOs\TaskData;
use App\DTOs\TaskFilterData;
use App\Models\Task;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TaskServiceInterface
{
    /**
     * @return LengthAwarePaginator<int, Task>
     */
    public function list(TaskFilterData $filters): LengthAwarePaginator;

    public function find(int $id): Task;

    public function create(TaskData $data, int $createdBy): Task;

    public function update(Task $task, TaskData $data): Task;

    public function delete(Task $task): void;

    public function assign(Task $task, ?int $assigneeId): Task;
}
