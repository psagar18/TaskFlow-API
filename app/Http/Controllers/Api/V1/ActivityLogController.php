<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ActivityLogController extends Controller
{
    public function index(Request $request, Task $task): JsonResponse
    {
        $this->authorize('view', $task);

        $logs = $task->activityLogs()
            ->with('causer')
            ->paginate((int) $request->integer('per_page', 15));

        return ActivityLogResource::collection($logs)->response();
    }
}
