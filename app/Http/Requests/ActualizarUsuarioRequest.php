<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use App\Models\User;

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
        $user = User::find($userId);

        $rules = [
            'nombre' => 'sometimes|required|string|min:3|max:100|regex:/^[\pL\s]+$/u', 
            'apellidos' => 'sometimes|required|string|min:3|max:150|regex:/^[\pL\s]+$/u',
            'email' => 'sometimes|required|email|unique:usuarios,email,' . $userId,
            'telefono' => 'nullable|string|max:20',
            'foto' => 'nullable|image|max:2048',
            'password' => ['sometimes', 'required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'activo' => 'sometimes|required|boolean',
            'rol' => 'prohibited',
        ];

        // solo validamos campos de médico si el usuario es médico
        if ($user && $user->esMedico()) {
            $rules['numero_colegiado'] = 'sometimes|required|string|unique:medicos,numero_colegiado,' . $userId . ',id_usuario';
            $rules['id_especialidad'] = 'sometimes|required|exists:especialidades,id';
        } else {
            // Si no eres médico, estos campos no están permitidos
            $rules['numero_colegiado'] = 'prohibited';
            $rules['id_especialidad'] = 'prohibited';
        }

        // solo validamos campos de paciente si el usuario es paciente
        if ($user && $user->esPaciente()) {
            $rules['numero_tarjeta'] = 'sometimes|required|digits:16';
            $rules['compania'] = 'sometimes|required|string|min:3|max:100';
        } else {
            // Si no eres paciente, estos campos no están permitidos
            $rules['numero_tarjeta'] = 'prohibited';
            $rules['compania'] = 'prohibited';
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
            'nombre.required' => 'El nombre es obligatorio',
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
            'rol.prohibited' => 'No puedes actualizar el rol del usuario',

            // Médico
            'numero_colegiado.required' => 'El número de colegiado es obligatorio',
            'numero_colegiado.unique' => 'Este número de colegiado ya está registrado',
            'numero_colegiado.prohibited' => 'El usuario no es médico así que no puedes actualizar el número de colegiado',
            'id_especialidad.required' => 'La especialidad es obligatoria',
            'id_especialidad.exists' => 'La especialidad seleccionada no existe',
            'id_especialidad.prohibited' => 'El usuario no es médico así que no puedes actualizar la especialidad',

            // Paciente
            'numero_tarjeta.required' => 'El número de tarjeta es obligatorio',
            'numero_tarjeta.digits' => 'El número de tarjeta debe tener 16 dígitos',
            'numero_tarjeta.prohibited' => 'No puedes actualizar el número de tarjeta si el usuario no es un paciente',
            'compania.required' => 'La compañía es obligatoria',
            'compania.min' => 'La compañía debe tener al menos 3 caracteres',
            'compania.max' => 'La compañía no puede tener más de 100 caracteres',
            'compania.prohibited' => 'No puedes actualizar la compañía si el usuario no es un paciente',
        ];
    }
}
