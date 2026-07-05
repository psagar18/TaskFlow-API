<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\Contracts\TaskRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\EloquentTaskRepository;
use App\Repositories\Eloquent\EloquentUserRepository;
use App\Services\ActivityLogService;
use App\Services\AuthService;
use App\Services\Contracts\ActivityLoggerInterface;
use App\Services\Contracts\AuthServiceInterface;
use App\Services\Contracts\TaskServiceInterface;
use App\Services\TaskService;
use Illuminate\Support\ServiceProvider;

final class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        TaskRepositoryInterface::class => EloquentTaskRepository::class,
        UserRepositoryInterface::class => EloquentUserRepository::class,
        ActivityLoggerInterface::class => ActivityLogService::class,
        TaskServiceInterface::class => TaskService::class,
        AuthServiceInterface::class => AuthService::class,
    ];
}
