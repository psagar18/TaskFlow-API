<?php

declare(strict_types=1);

namespace App\Filters\Task;

use Closure;
use Illuminate\Database\Eloquent\Builder;

final readonly class PriorityFilter implements TaskQueryFilter
{
    public function __construct(private ?string $priority) {}

    public function handle(Builder $query, Closure $next): Builder
    {
        if ($this->priority !== null) {
            $query->where('priority', $this->priority);
        }

        return $next($query);
    }
}
