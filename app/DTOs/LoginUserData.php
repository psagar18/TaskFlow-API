<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Http\Requests\Auth\LoginRequest;

final readonly class LoginUserData
{
    public function __construct(
        public string $email,
        public string $password,
        public ?string $deviceName = null,
    ) {}

    public static function fromRequest(LoginRequest $request): self
    {
        /** @var array{email: string, password: string, device_name?: string} $validated */
        $validated = $request->validated();

        return new self(
            email: $validated['email'],
            password: $validated['password'],
            deviceName: $validated['device_name'] ?? null,
        );
    }
}
