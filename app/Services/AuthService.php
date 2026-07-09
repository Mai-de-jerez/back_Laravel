<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Paciente;
use App\Enums\RolUsuario;
use App\Exceptions\Auth\InvalidCredentialsException;
use App\Exceptions\Auth\InvalidTokenException;
use App\Exceptions\Auth\InactiveUserException;
use Illuminate\Support\Facades\Password;


class AuthService
{
    public function __construct(
        private FileUploadService $fileUploadService
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

        $usuario->load(['medico', 'paciente']);
        $token = $usuario->createToken('api-token')->plainTextToken;

        return [
            'token'    => $token,
            'usuario'  => $usuario, 
        ];
    }

    public function login(array $datos): array
    {
        $usuario = User::porEmail($datos['email'])->first();

        if (!$usuario || !Hash::check($datos['password'], $usuario->password)) {
            throw new InvalidCredentialsException();
        }

        if (!$usuario->estaActivo()) {
            throw new InactiveUserException('Tu cuenta está desactivada. Contacta al administrador.');
        }

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

        if ($usuario && $usuario->currentAccessToken()) {
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