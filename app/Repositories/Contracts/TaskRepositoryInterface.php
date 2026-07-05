<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\DTOs\TaskFilterData;
use App\Models\Task;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TaskRepositoryInterface
{
    public function findOrFail(int $id): Task;

    /**
     * @return LengthAwarePaginator<int, Task>
     */
    public function paginate(TaskFilterData $filters): LengthAwarePaginator;

    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): Task;

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(Task $task, array $attributes): Task;

    public function delete(Task $task): bool;
}
