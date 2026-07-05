<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\User;

interface UserRepositoryInterface
{
    public function findByEmail(string $email): ?User;

    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): User;
}
