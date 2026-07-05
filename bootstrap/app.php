<?php

use App\Exceptions\InvalidCredentialsException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn (Request $request): bool => $request->is('api/*') || $request->expectsJson());

        $exceptions->render(fn (ValidationException $e): JsonResponse => response()->json([
            'message' => 'The given data was invalid.',
            'errors' => $e->errors(),
        ], $e->status));

        $exceptions->render(fn (AuthenticationException $e): JsonResponse => response()->json([
            'message' => 'Unauthenticated.',
        ], 401));

        $exceptions->render(fn (AuthorizationException $e): JsonResponse => response()->json([
            'message' => $e->getMessage() ?: 'This action is unauthorized.',
        ], 403));

        $exceptions->render(fn (InvalidCredentialsException $e): JsonResponse => response()->json([
            'message' => $e->getMessage(),
        ], 401));

        $exceptions->render(fn (ModelNotFoundException $e): JsonResponse => response()->json([
            'message' => 'The requested resource was not found.',
        ], 404));

        $exceptions->render(fn (HttpExceptionInterface $e): JsonResponse => response()->json([
            'message' => $e->getMessage() ?: 'An error occurred.',
        ], $e->getStatusCode()));
    })->create();
