<?php

declare(strict_types=1);

namespace App\Filters\Task;

use Closure;
use Illuminate\Database\Eloquent\Builder;

final readonly class AssignedToFilter implements TaskQueryFilter
{
    public function __construct(private ?int $assignedTo) {}

    public function handle(Builder $query, Closure $next): Builder
    {
        if ($this->assignedTo !== null) {
            $query->where('assigned_to', $this->assignedTo);
        }

        return $next($query);
    }
}
