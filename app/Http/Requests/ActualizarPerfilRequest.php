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

        $rules = [
            'nombre' => 'sometimes|required|string|min:3|max:100|regex:/^[\pL\s]+$/u',
            'apellidos' => 'sometimes|required|string|min:3|max:150|regex:/^[\pL\s]+$/u',
            'email' => 'sometimes|required|email|unique:usuarios,email,' . $userId,
            'telefono' => 'nullable|string|max:20',
            'foto' => 'nullable|image|max:2048',
            'password' => ['sometimes', 'required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'rol' => 'prohibited',
            'activo' => 'prohibited',
            'numero_colegiado' => 'prohibited',
            'id_especialidad' => 'prohibited',
        ];

        if ($usuario->esPaciente()) {
            $rules['numero_tarjeta'] = 'sometimes|required|digits:16';
            $rules['compania'] = 'sometimes|required|string|min:3|max:100';
        } else {
            $rules['numero_tarjeta'] = 'prohibited';
            $rules['compania'] = 'prohibited';
        }

        return $rules;
    }

    /**
     * Obtiene los mensajes de error personalizados para las reglas de validación.
     */
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio bro',
            'nombre.min' => 'El nombre debe tener al menos 3 caracteres',
            'nombre.max' => 'El nombre no puede tener más de 100 caracteres',
            'nombre.regex' => 'El nombre solo puede contener letras y espacios',
            'apellidos.required' => 'Los apellidos son obligatorios',
            'apellidos.min' => 'Los apellidos deben tener al menos 3 caracteres',
            'apellidos.max' => 'Los apellidos no pueden tener más de 150 caracteres',
            'apellidos.regex' => 'Los apellidos solo pueden contener letras y espacios',
            'email.required' => 'El email es obligatorio',
            'email.email' => 'El email no es válido',
            'email.unique' => 'Este email ya está registrado',
            'telefono.max' => 'El teléfono no puede tener más de 20 caracteres',
            'foto.image' => 'La foto debe ser una imagen válida',
            'foto.max' => 'La foto no puede superar los 2MB',
            'password.required' => 'La contraseña es obligatoria',
            'password.confirmed' => 'Las contraseñas no coinciden',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres',
            'password.mixedCase' => 'La contraseña debe contener al menos una mayúscula y una minúscula',
            'password.numbers' => 'La contraseña debe contener al menos un número',
            'numero_tarjeta.required' => 'El número de tarjeta es obligatorio',
            'numero_tarjeta.digits' => 'El número de tarjeta debe tener 16 dígitos',
            'numero_tarjeta.prohibited' => 'No puedes modificar el número de tarjeta, no eres paciente',
            'compania.required' => 'La compañía es obligatoria',
            'compania.min' => 'La compañía debe tener al menos 3 caracteres',
            'compania.max' => 'La compañía no puede tener más de 100 caracteres',
            'compania.prohibited' => 'No puedes modificar la compañía, no eres paciente',
            'rol.prohibited' => 'No puedes modificar el rol, no tienes permisos',
            'activo.prohibited' => 'No puedes modificar tu estado, no tienes permisos',
            'numero_colegiado.prohibited' => 'No puedes modificar el número de colegiado, no tienes permisos',
            'id_especialidad.prohibited' => 'No puedes modificar la especialidad, no tienes permisos',
        ];
    }
}
