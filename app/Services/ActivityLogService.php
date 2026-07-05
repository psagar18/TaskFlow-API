<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ActivityEvent;
use App\Models\ActivityLog;
use App\Services\Contracts\ActivityLoggerInterface;
use Illuminate\Database\Eloquent\Model;

final class ActivityLogService implements ActivityLoggerInterface
{
    public function log(
        ActivityEvent $event,
        string $description,
        Model $subject,
        ?int $causerId,
        array $properties = [],
    ): void {
        ActivityLog::query()->create([
            'event' => $event,
            'description' => $description,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'causer_id' => $causerId,
            'properties' => $properties,
        ]);
    }
}
