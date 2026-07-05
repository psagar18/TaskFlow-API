<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\ActivityEvent;
use App\Models\Task;
use App\Models\User;
use App\Services\Contracts\ActivityLoggerInterface;
use Illuminate\Support\Facades\Auth;

final readonly class TaskObserver
{
    public function __construct(private ActivityLoggerInterface $activityLogger) {}

    public function created(Task $task): void
    {
        $this->activityLogger->log(
            event: ActivityEvent::Created,
            description: "Task \"{$task->title}\" was created.",
            subject: $task,
            causerId: $this->currentUserId(),
        );
    }

    public function updated(Task $task): void
    {
        if ($task->wasChanged('status')) {
            $this->activityLogger->log(
                event: ActivityEvent::StatusChanged,
                description: "Task \"{$task->title}\" status changed to {$task->status->label()}.",
                subject: $task,
                causerId: $this->currentUserId(),
                properties: [
                    'from' => $task->getOriginal('status')?->value,
                    'to' => $task->status->value,
                ],
            );

            return;
        }

        if ($task->wasChanged('assigned_to')) {
            $event = $task->assigned_to === null ? ActivityEvent::Unassigned : ActivityEvent::Assigned;

            $this->activityLogger->log(
                event: $event,
                description: $task->assigned_to === null
                    ? "Task \"{$task->title}\" was unassigned."
                    : "Task \"{$task->title}\" was assigned.",
                subject: $task,
                causerId: $this->currentUserId(),
                properties: [
                    'from' => $task->getOriginal('assigned_to'),
                    'to' => $task->assigned_to,
                ],
            );

            return;
        }

        $this->activityLogger->log(
            event: ActivityEvent::Updated,
            description: "Task \"{$task->title}\" was updated.",
            subject: $task,
            causerId: $this->currentUserId(),
            properties: ['changed' => array_keys($task->getChanges())],
        );
    }

    public function deleted(Task $task): void
    {
        $this->activityLogger->log(
            event: ActivityEvent::Deleted,
            description: "Task \"{$task->title}\" was deleted.",
            subject: $task,
            causerId: $this->currentUserId(),
        );
    }

    private function currentUserId(): ?int
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user?->id;
    }
}
