<?php

declare(strict_types=1);

namespace App\Filters\Task;

use Closure;
use Illuminate\Database\Eloquent\Builder;

final readonly class SearchFilter implements TaskQueryFilter
{
    public function __construct(private ?string $search) {}

    public function handle(Builder $query, Closure $next): Builder
    {
        if ($this->search !== null && $this->search !== '') {
            $query->where(function (Builder $query): void {
                $query->where('title', 'like', "%{$this->search}%")
                    ->orWhere('description', 'like', "%{$this->search}%");
            });
        }

        return $next($query);
    }
}
