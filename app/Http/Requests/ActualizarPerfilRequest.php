<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ActualizarPerfilRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para hacer esta solicitud.
     */
    public function authorize(): bool
    {
        return true; // porque el usuario debe estar autenticado para actualizar su perfil.
    }

    /**
     * Obtiene las reglas de validación que se aplican a la solicitud.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $usuario = $this->user();
        $userId = $usuario->id;

        return [
            'nombre' => 'sometimes|string|min:3|max:100|regex:/^[\pL\s]+$/u',
            'apellidos' => 'sometimes|string|min:3|max:150|regex:/^[\pL\s]+$/u',
            'email' => 'sometimes|email|unique:usuarios,email,' . $userId,
            'telefono' => 'nullable|string|max:20',
            'foto' => 'nullable|image|max:2048',
            'password' => ['sometimes', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'numero_tarjeta' => 'sometimes|digits:16',
            'compania' => 'sometimes|string|min:3|max:100',
        ];
    }

    /**
     * Obtiene los mensajes de error personalizados para las reglas de validación.
     */
    public function messages(): array
    {
        return [
            'nombre.min' => 'El nombre debe tener al menos 3 caracteres bro',
            'nombre.max' => 'El nombre no puede tener más de 100 caracteres',
            'nombre.regex' => 'El nombre solo puede contener letras y espacios bro',
            'apellidos.min' => 'Los apellidos deben tener al menos 3 caracteres bro',
            'apellidos.max' => 'Los apellidos no pueden tener más de 150 caracteres bro',
            'apellidos.regex' => 'Los apellidos solo pueden contener letras y espacios bro',
            'email.email' => 'El email no es válido',
            'email.unique' => 'Este email ya está registrado bro',
            'telefono.max' => 'El teléfono no puede tener más de 20 caracteres bro',
            'foto.image' => 'La foto debe ser una imagen válida bro',
            'foto.max' => 'La foto no puede superar los 2MB bro',
            'password.confirmed' => 'Las contraseñas no coinciden bro',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres bro',
            'password.mixedCase' => 'La contraseña debe contener al menos una mayúscula y una minúscula bro',
            'password.numbers' => 'La contraseña debe contener al menos un número bro',
            'numero_tarjeta.digits' => 'El número de tarjeta debe tener 16 dígitos bro',
            'compania.min' => 'La compañía debe tener al menos 3 caracteres bro',
            'compania.max' => 'La compañía no puede tener más de 100 caracteres bro',
        ];
    }
}
