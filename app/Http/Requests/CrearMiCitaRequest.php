<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CrearMiCitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_medico' => 'required|integer|exists:medicos,id',
            'fecha' => 'required|date_format:Y-m-d|after_or_equal:today',
            'hora' => [
                'required',
                'date_format:H:i',
                function ($attribute, $value, $fail) {
                    [$h, $m] = explode(':', $value);
                    if ((int)$m % 15 !== 0) {
                        $fail('Las citas empiezan cada 15 minutos. Elige una hora que acabe en :00, :15, :30 o :45');
                    }
                },
            ],
            'motivo' => 'nullable|string|max:1000',
            'notas' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
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