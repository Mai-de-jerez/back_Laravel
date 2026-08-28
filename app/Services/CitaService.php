<?php

namespace App\Services;

use App\Models\Cita;
use App\Models\Horario;
use App\Models\Medico;
use App\Models\Paciente;
use App\Enums\DiaSemana;
use App\Enums\EstadoCita;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CitaService
{

    /**
     * Listar citas con filtros opcionales y paginación
     * @param array $filtros parametro que pasa los filtros para listar citas
     * @return array retorna un array con las citas filtradas y paginadas
     */
    public function obtenerTodasLasCitas(array $filtros = []): array
    {
        $query = Cita::with(['paciente.usuario', 'medico.usuario']);

        if (!empty($filtros['id'])) {
            $query->where('id', $filtros['id']);
        }

        if (!empty($filtros['id_medico'])) {
            $query->where('id_medico', $filtros['id_medico']);
        }

        if (!empty($filtros['id_paciente'])) {
            $query->where('id_paciente', $filtros['id_paciente']);
        }

        if (!empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }

        if (!empty($filtros['fecha'])) {
            $query->whereDate('fecha', $filtros['fecha']);
        }

        $paginator = $query->orderBy('id', 'desc')->paginate(15);

        return [
            'citas'         => $paginator->items(),
            'pagina_actual' => $paginator->currentPage(),
            'ultima_pagina' => $paginator->lastPage(),
            'por_pagina'    => $paginator->perPage(),
            'total'         => $paginator->total(),
        ];
    }

    /**
     * Crear una cita.
     * Duración fija de 15 minutos.
     * @throws NotFoundHttpException si el paciente o el médico no existen
     * @throws \InvalidArgumentException si el médico no tiene horario disponible
     * en esa fecha/hora, o si ya tiene otra cita activa que se solapa
     */
    public function crearCita(array $datos): Cita
    {
        $paciente = Paciente::find($datos['id_paciente']);
        if (!$paciente) {
            throw new NotFoundHttpException('Paciente no encontrado');
        }

        $medico = Medico::find($datos['id_medico']);
        if (!$medico) {
            throw new NotFoundHttpException('Médico no encontrado');
        }

        $fecha = Carbon::parse($datos['fecha']);
        $horaInicio = $datos['hora'];
        $horaFin = Carbon::parse($horaInicio)->addMinutes(15)->format('H:i');
        $diaSemana = DiaSemana::fromFecha($fecha);

        return DB::transaction(function () use ($datos, $paciente, $medico, $fecha, $horaInicio, $horaFin, $diaSemana) {

            // El médico debe tener un horario que cubra esa franja ese día de la semana
            if (!$this->estaDentroDeHorario($medico->id, $diaSemana, $horaInicio, $horaFin)) {
                throw new \InvalidArgumentException(
                    'El médico no tiene horario disponible en esa fecha y hora'
                );
            }

            // Comprobar si se solapa con otra cita activa usando el método privado
            if ($this->existeSolapeCita($medico->id, $fecha->toDateString(), $horaInicio, $horaFin, null, true)) {
                throw new \InvalidArgumentException(
                    'El médico ya tiene otra cita activa en ese horario'
                );
            }

            $cita = Cita::create([
                'id_paciente' => $paciente->id,
                'id_medico'   => $medico->id,
                'fecha'       => $fecha->toDateString(),
                'hora'        => $horaInicio,
                'estado'      => EstadoCita::ACTIVA,
                'motivo'      => $datos['motivo'] ?? null,
                'notas'       => $datos['notas'] ?? null,
            ]);

            Log::info('Cita creada', [
                'cita_id'     => $cita->id,
                'id_medico'   => $medico->id,
                'id_paciente' => $paciente->id,
                'fecha'       => $fecha->toDateString(),
                'hora'        => $horaInicio,
            ]);

            return $cita;
        });
    }   

    /**
     * Comprueba si el médico tiene turno de trabajo que cubra la franja indicada.
     */
    private function estaDentroDeHorario(
        int $medicoId,
        DiaSemana $diaSemana,
        string $horaInicio,
        string $horaFin
    ): bool {
        return Horario::where('id_medico', $medicoId)
            ->where('dia_semana', $diaSemana->value)
            ->where('hora_inicio', '<=', $horaInicio)
            ->where('hora_fin', '>=', $horaFin)
            ->exists();
    }

    /**
     * Comprueba si una cita se solapa con otra ya existente para ese médico y esa fecha.
     */
    private function existeSolapeCita(
        int $medicoId,
        string $fecha,
        string $horaInicio,
        string $horaFin,
        ?int $excluirCitaId = null,
        bool $bloquear = false
    ): bool {
        $query = Cita::where('id_medico', $medicoId)
            ->where('fecha', $fecha)
            ->where('estado', EstadoCita::ACTIVA)
            ->where('hora', '<', $horaFin)
            ->where('hora', '>=', $horaInicio);

        // Si estamos editando, ignoramos la propia cita que estamos modificando
        if ($excluirCitaId !== null) {
            $query->where('id', '!=', $excluirCitaId);
        }

        if ($bloquear) {
            $query->lockForUpdate();
        }

        return $query->exists();
    }
}