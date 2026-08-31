<?php

namespace App\Http\Requests;

use App\Enums\EstadoCita;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActualizarCitaRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para hacer esta petición.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para actualizar una cita.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id_medico' => ['sometimes', 'integer', 'exists:medicos,id'],
            'id_paciente' => ['sometimes', 'integer', 'exists:pacientes,id'],
            'fecha' => ['sometimes', 'date', 'after_or_equal:today'],
            'hora' => ['sometimes', 'date_format:H:i'],
            'estado' => ['sometimes', Rule::enum(EstadoCita::class)],
            'motivo' => ['nullable', 'string', 'max:500'],
            'notas' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Mensajes de error personalizados en español.
     */
    public function messages(): array
    {
        return [
            'id_medico.integer' => 'El identificador del médico debe ser un número entero.',
            'id_medico.exists' => 'El médico seleccionado no existe en el sistema.',
            'id_paciente.integer' => 'El identificador del paciente debe ser un número entero.',
            'id_paciente.exists' => 'El paciente seleccionado no existe en el sistema.',
            'fecha.date' => 'La fecha debe tener un formato válido.',
            'fecha.after_or_equal' => 'No se pueden programar o actualizar citas en fechas pasadas.',
            'hora.date_format' => 'La hora debe tener el formato HH:MM (24 horas).',
            'estado.Illuminate\Validation\Rules\Enum' => 'El estado de la cita proporcionado no es válido.',
            'motivo.max' => 'El motivo no puede superar los 500 caracteres.',
            'notas.max' => 'Las notas no pueden superar los 1000 caracteres.',
        ];
    }
}