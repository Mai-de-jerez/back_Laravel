<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_paciente' => 'required|integer|exists:pacientes,id',
            'id_medico' => 'required|integer|exists:medicos,id',
            'fecha' => 'required|date_format:Y-m-d|after_or_equal:today',
            'hora' => 'required|date_format:H:i',
            'motivo' => 'nullable|string|max:1000',
            'notas' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'id_paciente.required' => 'El paciente es obligatorio',
            'id_paciente.exists' => 'El paciente seleccionado no existe',
            'id_medico.required' => 'El médico es obligatorio',
            'id_medico.exists' => 'El médico seleccionado no existe',
            'fecha.required' => 'La fecha es obligatoria',
            'fecha.date_format' => 'La fecha debe tener formato YYYY-MM-DD',
            'fecha.after_or_equal' => 'La fecha no puede ser anterior a hoy',
            'hora.required' => 'La hora es obligatoria',
            'hora.date_format' => 'La hora debe tener formato HH:MM',
        ];
    }
}