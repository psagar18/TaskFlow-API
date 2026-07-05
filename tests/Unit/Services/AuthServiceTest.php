<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTOs\LoginUserData;
use App\Exceptions\InvalidCredentialsException;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_throws_when_the_user_does_not_exist(): void
    {
        $repository = Mockery::mock(UserRepositoryInterface::class);
        $repository->shouldReceive('findByEmail')->once()->with('missing@example.com')->andReturnNull();

        $service = new AuthService($repository);

        $this->expectException(InvalidCredentialsException::class);

        $service->login(new LoginUserData(email: 'missing@example.com', password: 'secret'));
    }

    #[Test]
    public function it_throws_when_the_password_is_incorrect(): void
    {
        $user = User::factory()->make(['password' => Hash::make('correct-password')]);

        $repository = Mockery::mock(UserRepositoryInterface::class);
        $repository->shouldReceive('findByEmail')->once()->andReturn($user);

        $service = new AuthService($repository);

        $this->expectException(InvalidCredentialsException::class);

        $service->login(new LoginUserData(email: $user->email, password: 'wrong-password'));
    }

    #[Test]
    public function it_returns_a_token_on_successful_login(): void
    {
        $user = User::factory()->create(['password' => Hash::make('correct-password')]);

        $repository = Mockery::mock(UserRepositoryInterface::class);
        $repository->shouldReceive('findByEmail')->once()->andReturn($user);

        $service = new AuthService($repository);

        $result = $service->login(new LoginUserData(email: $user->email, password: 'correct-password'));

        $this->assertSame($user->id, $result['user']->id);
        $this->assertIsString($result['token']);
    }
}
