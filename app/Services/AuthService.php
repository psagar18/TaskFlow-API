<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\LoginUserData;
use App\DTOs\RegisterUserData;
use App\Exceptions\InvalidCredentialsException;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Contracts\AuthServiceInterface;
use Illuminate\Support\Facades\Hash;

final readonly class AuthService implements AuthServiceInterface
{
    public function __construct(private UserRepositoryInterface $users) {}

    public function register(RegisterUserData $data): User
    {
        return $this->users->create([
            'name' => $data->name,
            'email' => $data->email,
            'password' => $data->password,
        ]);
    }

    public function login(LoginUserData $data): array
    {
        $user = $this->users->findByEmail($data->email);

        if ($user === null || ! Hash::check($data->password, $user->password)) {
            throw new InvalidCredentialsException;
        }

        $token = $user->createToken($data->deviceName ?? 'api-token')->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }
}
