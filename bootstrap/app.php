<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Http\Middleware\Authenticate;
use App\Http\Middleware\AdminMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php', 
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )

    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'jwt.auth' => Authenticate::class,
            'admin' => AdminMiddleware::class,
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions): void {

        $exceptions->render(function (App\Exceptions\Auth\InvalidCredentialsException $e) {
            return response()->json(['mensaje' => $e->getMessage()], $e->getCode());
        });

        $exceptions->render(function (App\Exceptions\Auth\InvalidTokenException $e) {
            return response()->json(['mensaje' => $e->getMessage()], $e->getCode());
        });

        $exceptions->render(function (App\Exceptions\Auth\EmailNotFoundException $e) {
            // Forzamos el mensaje genérico y el código 200 para despistar a atacantes
            return response()->json(['mensaje' => 'Enlace enviado a tu email'], 200);
        });

        $exceptions->render(function (App\Exceptions\Auth\InactiveUserException $e) {
            return response()->json(['mensaje' => $e->getMessage()], $e->getCode());
        });

        $exceptions->render(function (App\Exceptions\Files\FileUploadException $e) {
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

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException $e) {
            return response()->json(['mensaje' => 'Token no proporcionado'], 401);
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, Request $request) {
            return response()->json(['mensaje' => 'Recurso no encontrado'], 404);
        });

        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
            return response()->json(['mensaje' => 'Error de validación', 'errores' => $e->errors(),], 422);
        });
    })->create();
