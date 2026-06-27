<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Enums\RolUsuario;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Exceptions\Auth\InvalidCredentialsException;
use App\Exceptions\Auth\InvalidTokenException;
use App\Exceptions\Auth\EmailNotFoundException;
use App\Exceptions\Auth\InactiveUserException;

class AuthService
{

    public function __construct(private FileUploadService $fileUploadService) {}

    public function register(array $datos, $foto = null): array
    {
        $rutaFoto = $foto
            ? $this->fileUploadService->subirFoto($foto)
            : FileUploadService::DEFAULT_FOTO;

        $usuario = User::create([
            'nombre'    => $datos['nombre'],
            'apellidos' => $datos['apellidos'],
            'email'     => $datos['email'],
            'password'  => Hash::make($datos['password']),
            'telefono'  => $datos['telefono'] ?? null,
            'foto'      => $rutaFoto,
            'rol'       => RolUsuario::PACIENTE,
        ]);

        $token = JWTAuth::fromUser($usuario);

        return [
            'mensaje' => 'Usuario registrado correctamente',
            'token'   => $token,
            'usuario' => $usuario,
            'foto_url' => $this->fileUploadService->obtenerUrl($rutaFoto),
        ];
    }


    public function login(array $datos): array
    {
        $token = JWTAuth::attempt([
            'email'    => $datos['email'],
            'password' => $datos['password'],
        ]);

        if (!$token) {
            throw new InvalidCredentialsException();
        }

        $usuario = JWTAuth::user();

        if (!$usuario->activo) {
            throw new InactiveUserException();
        }

        return [
            'mensaje' => 'Login correcto',
            'token'   => $token,
            'usuario' => $usuario,
        ];
    }


    public function logout(): void
    {
        JWTAuth::invalidate(JWTAuth::getToken());
    }



    public function recuperarPassword(string $email): array
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            throw new EmailNotFoundException();
        }

        $token = JWTAuth::claims(['type' => 'password_reset'])->fromUser($user);

        $resetUrl = config('app.frontend_url') . '/reset-password?token=' . $token;

        Mail::send([], [], function ($message) use ($email, $resetUrl) {
            $message->to($email)
                ->subject('Restablecer contraseña')
                ->html('<h2>Restablecer contraseña</h2>
                        <p>Haz clic aquí: <a href="' . $resetUrl . '">Restablecer contraseña</a></p>
                        <p>Este enlace expira en 15 minutos.</p>');
        });

        return [
            'mensaje' => 'Enlace enviado a tu email',
            'token'   => $token, // temporal para pruebas
        ];
    }



    public function restablecerPassword(array $datos): void
    {
        try {
            $payload = JWTAuth::setToken($datos['token'])->getPayload();

            if ($payload->get('type') !== 'password_reset') {
                throw new InvalidTokenException('Token no válido para esta operación');
            }

            $user = JWTAuth::setToken($datos['token'])->authenticate();

            if (!$user) {
                throw new InvalidTokenException();
            }

            $user->update(['password' => Hash::make($datos['password'])]);

        } catch (TokenExpiredException $e) {
            Log::warning('Intento de reset con token expirado');
            throw new InvalidTokenException('El enlace ha expirado');
        } catch (TokenInvalidException $e) {
            Log::warning('Intento de reset con token inválido');
            throw new InvalidTokenException();
        } catch (JWTException $e) {
            Log::error('Error JWT en restablecerPassword: ' . $e->getMessage());
            throw new InvalidTokenException();
        }
    }
}