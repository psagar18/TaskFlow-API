<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Http\Requests\Task\IndexTaskRequest;

final readonly class TaskFilterData
{
    public function __construct(
        public ?string $status = null,
        public ?string $priority = null,
        public ?int $assignedTo = null,
        public ?string $search = null,
        public ?string $dueBefore = null,
        public ?string $dueAfter = null,
        public string $sortBy = 'created_at',
        public string $sortDirection = 'desc',
        public int $perPage = 15,
    ) {}

    public static function fromRequest(IndexTaskRequest $request): self
    {
        /** @var array{status?: string, priority?: string, assigned_to?: int, search?: string, due_before?: string, due_after?: string, sort_by?: string, sort_direction?: string, per_page?: int} $validated */
        $validated = $request->validated();

        return new self(
            status: $validated['status'] ?? null,
            priority: $validated['priority'] ?? null,
            assignedTo: isset($validated['assigned_to']) ? (int) $validated['assigned_to'] : null,
            search: $validated['search'] ?? null,
            dueBefore: $validated['due_before'] ?? null,
            dueAfter: $validated['due_after'] ?? null,
            sortBy: $validated['sort_by'] ?? 'created_at',
            sortDirection: $validated['sort_direction'] ?? 'desc',
            perPage: isset($validated['per_page']) ? (int) $validated['per_page'] : 15,
        );
    }
}
