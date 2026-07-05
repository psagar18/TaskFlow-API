<?php

declare(strict_types=1);

namespace App\Filters\Task;

use Closure;
use Illuminate\Database\Eloquent\Builder;

final readonly class SortFilter implements TaskQueryFilter
{
    /**
     * @var list<string>
     */
    private const array SORTABLE_COLUMNS = [
        'created_at',
        'due_date',
        'priority',
        'status',
        'title',
    ];

    public function __construct(
        private string $sortBy,
        private string $sortDirection,
    ) {}

    public function handle(Builder $query, Closure $next): Builder
    {
        $column = in_array($this->sortBy, self::SORTABLE_COLUMNS, true)
            ? $this->sortBy
            : 'created_at';

        $direction = strtolower($this->sortDirection) === 'asc' ? 'asc' : 'desc';

        $query->orderBy($column, $direction);

        return $next($query);
    }
}
