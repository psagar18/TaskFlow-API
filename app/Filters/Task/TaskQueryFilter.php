<?php

declare(strict_types=1);

namespace App\Filters\Task;

use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;

/**
 * Strategy contract for a single, composable task query filter.
 * Implementations are chained through Laravel's Pipeline in EloquentTaskRepository.
 */
interface TaskQueryFilter
{
    /**
     * @param Builder<Task> $query
     * @param \Closure(Builder<Task>): Builder<Task> $next
     * @return Builder<Task>
     */
    public function handle(Builder $query, \Closure $next): Builder;
}
