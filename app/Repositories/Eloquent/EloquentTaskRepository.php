<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\DTOs\TaskFilterData;
use App\Filters\Task\AssignedToFilter;
use App\Filters\Task\DueDateRangeFilter;
use App\Filters\Task\PriorityFilter;
use App\Filters\Task\SearchFilter;
use App\Filters\Task\SortFilter;
use App\Filters\Task\StatusFilter;
use App\Models\Task;
use App\Repositories\Contracts\TaskRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pipeline\Pipeline;

final class EloquentTaskRepository implements TaskRepositoryInterface
{
    public function __construct(private readonly Pipeline $pipeline) {}

    public function findOrFail(int $id): Task
    {
        return Task::query()->with(['creator', 'assignee'])->findOrFail($id);
    }

    public function paginate(TaskFilterData $filters): LengthAwarePaginator
    {
        $query = Task::query()->with(['creator', 'assignee']);

        $filterPipeline = [
            new StatusFilter($filters->status),
            new PriorityFilter($filters->priority),
            new AssignedToFilter($filters->assignedTo),
            new SearchFilter($filters->search),
            new DueDateRangeFilter($filters->dueAfter, $filters->dueBefore),
            new SortFilter($filters->sortBy, $filters->sortDirection),
        ];

        /** @var Builder<Task> $filtered */
        $filtered = $this->pipeline
            ->send($query)
            ->through($filterPipeline)
            ->via('handle')
            ->thenReturn();

        return $filtered->paginate($filters->perPage)->withQueryString();
    }

    public function create(array $attributes): Task
    {
        // refresh() pulls back DB-level defaults (e.g. status) that aren't in $attributes.
        return Task::query()->create($attributes)->refresh();
    }

    public function update(Task $task, array $attributes): Task
    {
        $task->update($attributes);

        return $task->refresh();
    }

    public function delete(Task $task): bool
    {
        return (bool) $task->delete();
    }
}
