<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\DTOs\LoginUserData;
use App\DTOs\RegisterUserData;
use App\Models\User;

interface AuthServiceInterface
{
    public function register(RegisterUserData $data): User;

    /**
     * @return array{user: User, token: string}
     */
    public function login(LoginUserData $data): array;

    public function logout(User $user): void;
}
