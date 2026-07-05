<?php

declare(strict_types=1);

namespace App\Filters\Task;

use Closure;
use Illuminate\Database\Eloquent\Builder;

final readonly class DueDateRangeFilter implements TaskQueryFilter
{
    public function __construct(
        private ?string $dueAfter,
        private ?string $dueBefore,
    ) {}

    public function handle(Builder $query, Closure $next): Builder
    {
        if ($this->dueAfter !== null) {
            $query->where('due_date', '>=', $this->dueAfter);
        }

        if ($this->dueBefore !== null) {
            $query->where('due_date', '<=', $this->dueBefore);
        }

        return $next($query);
    }
}
