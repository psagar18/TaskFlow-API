<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ActivityLog
 */
final class ActivityLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event' => $this->event->value,
            'description' => $this->description,
            'properties' => $this->properties,
            'causer' => $this->whenLoaded('causer', fn () => $this->causer ? new UserResource($this->causer) : null),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
