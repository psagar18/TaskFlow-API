<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Enums\ActivityEvent;
use Illuminate\Database\Eloquent\Model;

interface ActivityLoggerInterface
{
    /**
     * @param array<string, mixed> $properties
     */
    public function log(
        ActivityEvent $event,
        string $description,
        Model $subject,
        ?int $causerId,
        array $properties = [],
    ): void;
}
