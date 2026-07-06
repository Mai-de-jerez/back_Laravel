<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class CrearUsuarioRequest extends FormRequest
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
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
        public function rules(): array
    {
        $rules = [
            'nombre' => 'required|string|min:3|max:100',
            'apellidos' => 'required|string|min:3|max:150',
            'email' => 'required|email|unique:usuarios,email',
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'telefono' => 'nullable|string|max:20',
            'foto' => 'nullable|image|max:2048',
            'rol' => 'required|in:admin,medico,paciente',
        ];

        // validación condicional
        if ($this->input('rol') === 'medico') {
            $rules['numero_colegiado'] = 'required|string|unique:medicos,numero_colegiado';
            $rules['id_especialidad'] = 'required|exists:especialidades,id';
        }

        if ($this->input('rol') === 'paciente') {
            $rules['numero_tarjeta'] = 'required|digits:16';
            $rules['compania'] = 'required|string|min:3|max:100';
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
            'apellidos.required' => 'Los apellidos son obligatorios',
            'apellidos.min' => 'Los apellidos deben tener al menos 3 caracteres',
            'apellidos.max' => 'Los apellidos no pueden tener más de 150 caracteres',
            'email.required' => 'El email es obligatorio',
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
            'rol.required' => 'El rol es obligatorio',
            'rol.in' => 'El rol debe ser admin, medico o paciente',

            // Médico
            'numero_colegiado.required' => 'El número de colegiado es obligatorio',
            'numero_colegiado.unique' => 'Este número de colegiado ya está registrado',
            'id_especialidad.required' => 'La especialidad es obligatoria',
            'id_especialidad.exists' => 'La especialidad seleccionada no existe',

            // Paciente
            'numero_tarjeta.required' => 'El número de tarjeta es obligatorio',
            'numero_tarjeta.digits' => 'El número de tarjeta debe tener 16 dígitos',
            'compania.required' => 'La compañía es obligatoria',
            'compania.min' => 'La compañía debe tener al menos 3 caracteres',
            'compania.max' => 'La compañía no puede tener más de 100 caracteres',
        ];
    }
}
