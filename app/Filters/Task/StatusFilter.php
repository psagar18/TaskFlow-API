<?php

declare(strict_types=1);

namespace App\Filters\Task;

use Closure;
use Illuminate\Database\Eloquent\Builder;

final readonly class StatusFilter implements TaskQueryFilter
{
    public function __construct(private ?string $status) {}

    public function handle(Builder $query, Closure $next): Builder
    {
        if ($this->status !== null) {
            $query->where('status', $this->status);
        }

        return $next($query);
    }
}
