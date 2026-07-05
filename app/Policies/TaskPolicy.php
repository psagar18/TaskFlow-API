<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

final class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Task $task): bool
    {
        return $user->isAdmin()
            || $user->isManager()
            || $task->created_by === $user->id
            || $task->assigned_to === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    public function update(User $user, Task $task): bool
    {
        return $user->isAdmin()
            || $user->isManager()
            || $task->created_by === $user->id
            || $task->assigned_to === $user->id;
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->isAdmin() || $task->created_by === $user->id;
    }

    public function assign(User $user, Task $task): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    public function restore(User $user, Task $task): bool
    {
        return $user->isAdmin();
    }
}
