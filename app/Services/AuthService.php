<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use App\Models\User;
use App\Models\Paciente;
use App\Enums\RolUsuario;
use App\Exceptions\Auth\InvalidCredentialsException;
use App\Exceptions\Auth\InvalidTokenException;
use App\Exceptions\Auth\InactiveUserException;



class AuthService
{
    public function __construct(
        private FileUploadService $fileUploadService
    ) {}

    public function registro(array $datos, $foto = null): array
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
            try {
                $this->fileUploadService->eliminarFoto($rutaFoto);
            } catch (\Exception $eLimpieza) {
                Log::error('Fallo limpiando foto tras error de registro: ' . $eLimpieza->getMessage(), [
                    'ruta' => $rutaFoto
                ]);
            }

            Log::error('Error al registrar usuario: ' . $e->getMessage(), [
                'email' => $datos['email'] ?? 'unknown'
            ]);
            throw $e;
        }

        $usuario->load(['paciente']);
        $token = $usuario->createToken('api-token')->plainTextToken;

        return [
            'token'    => $token,
            'usuario'  => $usuario, 
        ];
    }

    public function login(array $datos): array
    {
        $throttleKey = Str::lower($datos['email']) . '|' . request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $segundos = RateLimiter::availableIn($throttleKey);
            throw new TooManyRequestsHttpException(
                $segundos, 
                "Demasiados intentos de acceso. Inténtalo de nuevo en {$segundos} segundos."
            );
        }

        $usuario = User::porEmail($datos['email'])->first();

        if (!$usuario || !Hash::check($datos['password'], $usuario->password)) {
            RateLimiter::hit($throttleKey, 60);
            throw new InvalidCredentialsException();
        }

        if (!$usuario->estaActivo()) {
            throw new InactiveUserException('Tu cuenta está desactivada. Contacta al administrador.');
        }

        // Si el login es exitoso, limpiamos los intentos fallidos
        RateLimiter::clear($throttleKey);

        // Destruimos cualquier token anterior del usuario antes de crear el nuevo 
        // (no queremos un cementerio de tokens)
        $usuario->tokens()->delete();

        $usuario->load(['medico', 'paciente']);
        $token = $usuario->createToken('api-token')->plainTextToken;

        return [
            'token'   => $token,
            'usuario' => $usuario, 
        ];
    }

    public function logout(): void
    {
        $usuario = Auth::user();

        if ($usuario && method_exists($usuario, 'currentAccessToken') && $usuario->currentAccessToken()) {
            $usuario->currentAccessToken()->delete();
            Log::info('Usuario cerró sesión', ['user_id' => $usuario->id]);
        }
    }

    public function recuperarPassword(string $email): void
    {
        $status = Password::sendResetLink(['email' => $email]);

        Log::info('Solicitud de recuperación procesada', ['email' => $email, 'status' => $status]);
    }

    public function restablecerPassword(array $datos): void
    {
        $status = Password::reset(
            [
                'email' => $datos['email'],
                'password' => $datos['password'],
                'password_confirmation' => $datos['password_confirmation'],
                'token' => $datos['token'],
            ],
            function ($user, $password) {
                $user->update(['password' => Hash::make($password)]);
            }
        );

        if ($status !== Password::PasswordReset) {
            throw new InvalidTokenException(__($status));
        }

        Log::info('Contraseña restablecida', ['email' => $datos['email']]);
    }
}