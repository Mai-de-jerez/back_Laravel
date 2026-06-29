<?php

namespace App\Services;

use App\Models\User;
use App\Models\Paciente;
use Illuminate\Support\Facades\DB;
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
use App\Mail\PasswordResetMail;

class AuthService
{
    public function __construct(
        private FileUploadService $fileUploadService,
        private UserService $userService 
    ) {}

    public function register(array $datos, $foto = null): array
    {
        $rutaFoto = $foto
            ? $this->fileUploadService->subirFoto($foto)
            : $this->fileUploadService->getFotoDefault();

        $usuario = null;

        try {
            $usuario = DB::transaction(function () use ($datos, $rutaFoto) {
                
                $nuevoUsuario = User::create([
                    'nombre'    => $datos['nombre'],
                    'apellidos' => $datos['apellidos'],
                    'email'     => $datos['email'],
                    'password'  => Hash::make($datos['password']),
                    'telefono'  => $datos['telefono'] ?? null,
                    'foto'      => $rutaFoto,
                    'rol'       => RolUsuario::PACIENTE,
                    'activo'    => true, 
                ]);

                Paciente::create([
                    'id_usuario'     => $nuevoUsuario->id,
                    'numero_tarjeta' => $datos['numero_tarjeta'], 
                    'compania'       => $datos['compania'],
                ]);

                return $nuevoUsuario;
            });

        } catch (\Exception $e) {
            $this->fileUploadService->eliminarFoto($rutaFoto);
            Log::error('Error al registrar usuario: ' . $e->getMessage(), [
                'email' => $datos['email'] ?? 'unknown'
            ]);
            throw $e;
        }

        $token = JWTAuth::fromUser($usuario);

        return [
            'mensaje'  => 'Usuario registrado correctamente',
            'token'    => $token,
            'usuario'  => $this->formatearUsuario($usuario), 
            'foto_url' => $this->fileUploadService->obtenerUrl($rutaFoto),
        ];
    }

    public function login(array $datos): array
    {
        $user = User::porEmail($datos['email'])->first();
        
        if (!$user) {
            throw new InvalidCredentialsException();
        }

        if (!$user->estaActivo()) {
            throw new InactiveUserException('Tu cuenta está desactivada. Contacta al administrador.');
        }

        $token = JWTAuth::attempt([
            'email'    => $datos['email'],
            'password' => $datos['password'],
        ]);

        if (!$token) {
            throw new InvalidCredentialsException();
        }

        $usuario = JWTAuth::user();

        return [
            'mensaje' => 'Login correcto',
            'token'   => $token,
            'usuario' => $this->formatearUsuario($usuario), 
        ];
    }

    public function logout(): void
    {
        $token = JWTAuth::getToken();
        
        if ($token) {
            JWTAuth::invalidate($token);
            Log::info('Usuario cerró sesión', ['user_id' => JWTAuth::user()?->id]);
        }
    }

    public function recuperarPassword(string $email): array
    {
        $user = User::porEmail($email)->first();

        if (!$user) {
            throw new EmailNotFoundException();
        }

        if (!$user->estaActivo()) {
            Log::warning('Intento de recuperación de usuario inactivo', ['email' => $email]);
            // MENTIMOS por seguridad
            return ['mensaje' => 'Enlace enviado a tu email'];
        }

        $token = JWTAuth::claims([
            'type' => 'password_reset',
            'user_id' => $user->id 
        ])->fromUser($user);

        $resetUrl = config('app.frontend_url') . '/auth/restablecer-password?token=' . $token;

        Mail::to($email)->send(new PasswordResetMail($resetUrl));

        Log::info('Email de recuperación enviado', ['email' => $email, 'user_id' => $user->id]);

        return [
            'mensaje' => 'Enlace enviado a tu email',
        ];
    }

    public function restablecerPassword(array $datos): void
    {
        try {
            $payload = JWTAuth::setToken($datos['token'])->getPayload();

            if ($payload->get('type') !== 'password_reset') {
                throw new InvalidTokenException('Token no válido para esta operación');
            }

            $userId = $payload->get('user_id');
            $user = User::find($userId);

            if (!$user) {
                throw new InvalidTokenException('Usuario no encontrado');
            }

            $userToken = JWTAuth::setToken($datos['token'])->authenticate();
            if (!$userToken || $userToken->id !== $user->id) {
                throw new InvalidTokenException('Token inválido');
            }

            // actualizar password y forzar logout de otras sesiones
            $user->update([
                'password' => Hash::make($datos['password'])
            ]);

            // invalidar el token de recuperación
            JWTAuth::invalidate(JWTAuth::getToken());

            Log::info('Contraseña restablecida', ['user_id' => $user->id, 'email' => $user->email]);

        } catch (TokenExpiredException $e) {
            Log::warning('Intento de reset con token expirado');
            throw new InvalidTokenException('El enlace ha expirado. Solicita uno nuevo.');
        } catch (TokenInvalidException $e) {
            Log::warning('Intento de reset con token inválido');
            throw new InvalidTokenException('Token inválido. Solicita un nuevo enlace.');
        } catch (JWTException $e) {
            Log::error('Error JWT en restablecerPassword: ' . $e->getMessage());
            throw new InvalidTokenException('Error al procesar la solicitud');
        }
    }

    /**
     * Método para formatear usuario en respuestas
     */
    private function formatearUsuario(User $usuario): array
    {
        return [
            'id' => $usuario->id,
            'nombre' => $usuario->nombre,
            'apellidos' => $usuario->apellidos,
            'nombre_completo' => $usuario->nombre_completo,
            'email' => $usuario->email,
            'telefono' => $usuario->telefono,
            'foto' => $usuario->foto,
            'foto_url' => $this->fileUploadService->obtenerUrl($usuario->foto),
            'rol' => $usuario->rol?->value,
            'rol_label' => $usuario->rol_label,
            'activo' => $usuario->activo,
            'perfil' => $usuario->perfil, // Médico o Paciente
        ];
    }

    /**
     * Refrescar token
     */
    public function refreshToken(): array
    {
        try {
            $newToken = JWTAuth::refresh(JWTAuth::getToken());
            $usuario = JWTAuth::user();

            return [
                'mensaje' => 'Token refrescado correctamente',
                'token' => $newToken,
                'usuario' => $this->formatearUsuario($usuario)
            ];
        } catch (JWTException $e) {
            Log::error('Error al refrescar token: ' . $e->getMessage());
            throw new InvalidTokenException('No se pudo refrescar el token');
        }
    }
}