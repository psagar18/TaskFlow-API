<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use Illuminate\Support\Carbon;

final readonly class TaskData
{
    public function __construct(
        public string $title,
        public ?string $description,
        public TaskPriority $priority,
        public ?Carbon $dueDate,
        public ?int $assignedTo,
        public ?TaskStatus $status = null,
    ) {}

    public static function fromStoreRequest(StoreTaskRequest $request): self
    {
        /** @var array{title: string, description?: string|null, priority?: string, due_date?: string|null, assigned_to?: int|null} $validated */
        $validated = $request->validated();

        return new self(
            title: $validated['title'],
            description: $validated['description'] ?? null,
            priority: isset($validated['priority'])
                ? TaskPriority::from($validated['priority'])
                : TaskPriority::Medium,
            dueDate: isset($validated['due_date']) ? Carbon::parse($validated['due_date']) : null,
            assignedTo: $validated['assigned_to'] ?? null,
        );
    }

    public static function fromUpdateRequest(UpdateTaskRequest $request): self
    {
        /** @var array{title: string, description?: string|null, priority?: string, status?: string, due_date?: string|null, assigned_to?: int|null} $validated */
        $validated = $request->validated();

        return new self(
            title: $validated['title'],
            description: $validated['description'] ?? null,
            priority: TaskPriority::from($validated['priority']),
            dueDate: isset($validated['due_date']) ? Carbon::parse($validated['due_date']) : null,
            assignedTo: $validated['assigned_to'] ?? null,
            status: isset($validated['status']) ? TaskStatus::from($validated['status']) : null,
        );
    }

    /**
     * Note: description, due_date and assigned_to are intentionally kept even when
     * null so a full update can clear them. status is omitted unless explicitly set,
     * since creating a task never assigns a status.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $attributes = [
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority->value,
            'due_date' => $this->dueDate,
            'assigned_to' => $this->assignedTo,
        ];

        if ($this->status instanceof TaskStatus) {
            $attributes['status'] = $this->status->value;
        }

        return $attributes;
    }
}
