<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ActualizarUsuarioRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para hacer esta solicitud.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Obtiene las reglas de validación que se aplican a la solicitud.
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->route('id');

        $rules = [
            'nombre' => 'sometimes|string|min:3|max:100|regex:/^[\pL\s]+$/u',
            'apellidos' => 'sometimes|string|min:3|max:150|regex:/^[\pL\s]+$/u',
            'email' => 'sometimes|email|unique:usuarios,email,' . $userId,
            'telefono' => 'nullable|string|max:20',
            'foto' => 'nullable|image|max:2048',
            'password' => ['sometimes', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'rol' => 'sometimes|in:admin,medico,paciente',
            'activo' => 'sometimes|boolean',
        ];

        // validación condicional según rol
        
        // medico
        if ($this->input('rol') === 'medico') {
            $rules['numero_colegiado'] = 'sometimes|string|unique:medicos,numero_colegiado,' . $userId . ',id_usuario';
            $rules['id_especialidad'] = 'sometimes|exists:especialidades,id';
        }

        // paciente
        if ($this->input('rol') === 'paciente') {
            $rules['numero_tarjeta'] = 'sometimes|digits:16';
            $rules['compania'] = 'sometimes|string|min:3|max:100';
        }

        return $rules;
    }

    /**
     * Obtiene los mensajes de error personalizados.
     */
    public function messages(): array
    {
        return [
            // Campos comunes
            'nombre.min' => 'El nombre debe tener al menos 3 caracteres',
            'nombre.max' => 'El nombre no puede tener más de 100 caracteres',
            'nombre.regex' => 'El nombre solo puede contener letras y espacios',
            'apellidos.min' => 'Los apellidos deben tener al menos 3 caracteres',
            'apellidos.max' => 'Los apellidos no pueden tener más de 150 caracteres',
            'apellidos.regex' => 'Los apellidos solo pueden contener letras y espacios',
            'email.email' => 'El email no es válido',
            'email.unique' => 'Este email ya está registrado',
            'telefono.max' => 'El teléfono no puede tener más de 20 caracteres',
            'foto.image' => 'La foto debe ser una imagen válida',
            'foto.max' => 'La foto no puede superar los 2MB',
            'password.confirmed' => 'Las contraseñas no coinciden',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres',
            'password.mixedCase' => 'La contraseña debe contener al menos una mayúscula y una minúscula',
            'password.numbers' => 'La contraseña debe contener al menos un número',
            'rol.in' => 'El rol debe ser admin, medico o paciente',

            // Médico
            'numero_colegiado.unique' => 'Este número de colegiado ya está registrado',
            'id_especialidad.exists' => 'La especialidad seleccionada no existe',

            // Paciente
            'numero_tarjeta.digits' => 'El número de tarjeta debe tener 16 dígitos',
            'compania.min' => 'La compañía debe tener al menos 3 caracteres',
            'compania.max' => 'La compañía no puede tener más de 100 caracteres',
        ];
    }
}
