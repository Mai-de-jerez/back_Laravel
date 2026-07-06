<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para hacer esta solicitud.
     */
    public function authorize(): bool
    {
        return true; // en principio cualquier usuario puede registrarse, así que devolvemos true
    }

    /**
     * Obtiene las reglas de validación que se aplican a la solicitud.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombre' => 'required|string|min:3|max:100|regex:/^[\pL\s]+$/u',
            'apellidos' => 'required|string|min:3|max:150|regex:/^[\pL\s]+$/u',
            'email' => 'required|email|unique:usuarios,email',
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'telefono' => 'nullable|string|max:20',
            'foto' => 'nullable|file|image|max:2048',
            'numero_tarjeta' => 'required|digits:16',
            'compania' => 'required|string|min:3|max:100',
        ];
    }

    /**
     * Obtiene los mensajes de error personalizados para las reglas de validación.
     */
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio',
            'nombre.regex' => 'El nombre solo puede contener letras y espacios',
            'apellidos.required' => 'Los apellidos son obligatorios',
            'apellidos.regex' => 'Los apellidos solo pueden contener letras y espacios',
            'email.required' => 'El email es obligatorio bro',
            'email.email' => 'El email no es válido',
            'email.unique' => 'Este email ya está registrado',
            'password.required' => 'La contraseña es obligatoria',
            'password.confirmed' => 'Las contraseñas no coinciden',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres',
            'password.mixedCase' => 'La contraseña debe contener al menos una letra mayúscula y una minúscula',
            'password.numbers' => 'La contraseña debe contener al menos un número',
            'telefono.max' => 'El teléfono no puede tener más de 20 caracteres',
            'foto.image' => 'La foto debe ser una imagen válida',
            'foto.max' => 'La foto no puede superar los 2MB',   
            'numero_tarjeta.required' => 'El número de tarjeta es obligatorio',
            'numero_tarjeta.digits' => 'El número de tarjeta debe tener 16 dígitos',
            'compania.required' => 'La compañía es obligatoria',
            'compania.min' => 'La compañía debe tener al menos 3 caracteres',
            'compania.max' => 'La compañía no puede tener más de 100 caracteres',
        ];
    }
}
