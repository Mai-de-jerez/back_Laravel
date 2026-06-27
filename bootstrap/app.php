<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

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
        $exceptions->render(function (App\Exceptions\Auth\InvalidCredentialsException $e) {
            return response()->json(['mensaje' => $e->getMessage()], $e->getCode());
        });

        $exceptions->render(function (App\Exceptions\Auth\InvalidTokenException $e) {
            return response()->json(['mensaje' => $e->getMessage()], $e->getCode());
        });

        $exceptions->render(function (App\Exceptions\Auth\EmailNotFoundException $e) {
            return response()->json(['mensaje' => $e->getMessage()], $e->getCode());
        });

        $exceptions->render(function (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['mensaje' => 'Token expirado'], 401);
        });

        $exceptions->render(function (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['mensaje' => 'Token inválido'], 401);
        });

        $exceptions->render(function (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['mensaje' => 'Token no proporcionado'], 401);
        });

        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
            return response()->json(['mensaje' => 'Error de validación', 'errores' => $e->errors(),], 422);
        });

        $exceptions->render(function (App\Exceptions\Auth\InactiveUserException $e) {
            return response()->json(['mensaje' => $e->getMessage()], $e->getCode());
        });

        $exceptions->render(function (App\Exceptions\FileUploadException $e) {
            return response()->json(['mensaje' => $e->getMessage()], $e->getCode());
        });
    })->create();
