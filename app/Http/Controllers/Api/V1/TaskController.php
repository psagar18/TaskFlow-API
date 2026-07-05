<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTOs\TaskData;
use App\DTOs\TaskFilterData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Task\AssignTaskRequest;
use App\Http\Requests\Task\IndexTaskRequest;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Models\User;
use App\Services\Contracts\TaskServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TaskController extends Controller
{
    public function __construct(private readonly TaskServiceInterface $taskService) {}

    public function index(IndexTaskRequest $request): JsonResponse
    {
        $tasks = $this->taskService->list(TaskFilterData::fromRequest($request));

        return TaskResource::collection($tasks)->response();
    }

    public function store(StoreTaskRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $task = $this->taskService->create(TaskData::fromStoreRequest($request), $user->id);

        return (new TaskResource($task->load(['creator', 'assignee'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Task $task): JsonResponse
    {
        $this->authorize('view', $task);

        return (new TaskResource($task->load(['creator', 'assignee'])))->response();
    }

    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        $task = $this->taskService->update($task, TaskData::fromUpdateRequest($request));

        return (new TaskResource($task->load(['creator', 'assignee'])))->response();
    }

    public function destroy(Request $request, Task $task): JsonResponse
    {
        $this->authorize('delete', $task);

        $this->taskService->delete($task);

        return response()->json(status: 204);
    }

    public function assign(AssignTaskRequest $request, Task $task): JsonResponse
    {
        /** @var array{assigned_to?: int|null} $validated */
        $validated = $request->validated();

        $task = $this->taskService->assign($task, $validated['assigned_to'] ?? null);

        return (new TaskResource($task->load(['creator', 'assignee'])))->response();
    }
}
