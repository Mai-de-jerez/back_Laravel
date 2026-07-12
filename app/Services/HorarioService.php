<?php

namespace App\Services;

use App\Models\Horario;
use App\Models\Medico;
use App\Enums\DiaSemana;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class HorarioService
{
    /**
     * Obtener todos los horarios de un médico para que pueda verlos (médico)
     */
    public function obtenerHorarioMedico(int $medicoId): \Illuminate\Database\Eloquent\Collection
    {
        return Horario::where('id_medico', $medicoId)
            ->orderBy('dia_semana')
            ->orderBy('hora_inicio')
            ->get();
    }

    /**
     * Obtener todos los horarios (admin)
     */
    public function obtenerTodosLosHorarios(): \Illuminate\Database\Eloquent\Collection
    {
        return Horario::with('medico.usuario')
            ->orderBy('id_medico')
            ->orderBy('dia_semana')
            ->orderBy('hora_inicio')
            ->get();
    }

    /**
     * Obtener un horario por ID (admin)
     */
    public function obtenerHorarioPorId(int $horarioId): ?Horario
    {
        return Horario::with('medico.usuario')->find($horarioId);
    }

    /**
     * Crear un horario para un médico (admin)
     * @param int $medicoId id del médico
     * @param array $datos ['dia_semana' => string, 'hora_inicio' => string, 'hora_fin' => string]
     * @throws \InvalidArgumentException Si la hora de inicio es mayor o igual a la hora de fin
     * @throws \InvalidArgumentException Si el horario se solapa con otro existente
     * @throws NotFoundHttpException Si el médico no existe
     * @return Horario
     */
    public function crearHorario(int $medicoId, array $datos): Horario
    {
        $medico = Medico::find($medicoId);
        if (!$medico) {
            throw new NotFoundHttpException('Médico no encontrado');
        }

        if ($datos['hora_inicio'] >= $datos['hora_fin']) {
            throw new \InvalidArgumentException('La hora de inicio debe ser menor que la hora de fin');
        }

        return DB::transaction(function () use ($medicoId, $datos) {
            $solapa = $this->existeSolape(
                $medicoId,
                $datos['dia_semana'],
                $datos['hora_inicio'],
                $datos['hora_fin'],
                excluirId: null,
                bloquear: true
            );

            if ($solapa) {
                throw new \InvalidArgumentException('El horario se solapa con otro ya existente para ese día');
            }

            $horario = Horario::create([
                'id_medico' => $medicoId,
                'dia_semana' => $datos['dia_semana'],
                'hora_inicio' => $datos['hora_inicio'],
                'hora_fin' => $datos['hora_fin'],
            ]);

            Log::info('Horario creado', [
                'medico_id' => $medicoId,
                'dia' => $datos['dia_semana'],
                'hora_inicio' => $datos['hora_inicio'],
                'hora_fin' => $datos['hora_fin'],
            ]);

            return $horario;
        });
    }

    /**
     * Actualizar un horario (solo el admin puede)
     */
    public function actualizarHorario(int $horarioId, array $datos): Horario
    {
        $horario = Horario::find($horarioId);

        if (!$horario) {
            throw new NotFoundHttpException('Horario no encontrado');
        }

        $dia = $datos['dia_semana'] ?? $horario->dia_semana->value;
        $horaInicio = $datos['hora_inicio'] ?? $horario->hora_inicio->format('H:i');
        $horaFin = $datos['hora_fin'] ?? $horario->hora_fin->format('H:i');

        if ($horaInicio >= $horaFin) {
            throw new \InvalidArgumentException('La hora de inicio debe ser menor que la hora de fin');
        }

        return DB::transaction(function () use ($horario, $datos, $dia, $horaInicio, $horaFin) {
            $solapa = $this->existeSolape(
                $horario->id_medico,
                $dia,
                $horaInicio,
                $horaFin,
                excluirId: $horario->id,
                bloquear: true
            );

            if ($solapa) {
                throw new \InvalidArgumentException('El horario se solapa con otro ya existente para ese día');
            }

            $horario->update($datos);

            Log::info('Horario actualizado por admin', [
                'horario_id' => $horario->id,
                'medico_id' => $horario->id_medico,
                'datos_actualizados' => $datos,
            ]);

            return $horario;
        });
    }

    /**
     * Eliminar un horario
     */
    public function eliminarHorario(int $horarioId, int $medicoId): void
    {
        $horario = Horario::where('id', $horarioId)
            ->where('id_medico', $medicoId)
            ->first();

        if (!$horario) {
            throw new NotFoundHttpException('Horario no encontrado');
        }

        DB::transaction(function () use ($horario) {
            $horario->delete();

            Log::info('Horario eliminado', [
                'horario_id' => $horario->id,
                'medico_id' => $horario->id_medico,
            ]);
        });
    }

    /**
     * Comprueba si una franja horaria se solapa con otra ya existente
     * para ese médico y ese día.
     *
     * @param int $medicoId
     * @param string $dia día de la semana (valor del enum DiaSemana)
     * @param string $horaInicio formato H:i
     * @param string $horaFin formato H:i
     * @param int|null $excluirId id de horario a excluir (útil al actualizar, para no chocar consigo mismo)
     * @param bool $bloquear si true, aplica lockForUpdate (solo tiene sentido dentro de una transacción)
     */
    private function existeSolape(
        int $medicoId,
        string $dia,
        string $horaInicio,
        string $horaFin,
        ?int $excluirId = null,
        bool $bloquear = false
    ): bool {
        $query = Horario::where('id_medico', $medicoId)
            ->where('dia_semana', $dia)
            ->where('hora_inicio', '<', $horaFin)
            ->where('hora_fin', '>', $horaInicio);

        if ($excluirId !== null) {
            $query->where('id', '!=', $excluirId);
        }

        if ($bloquear) {
            $query->lockForUpdate();
        }

        return $query->exists();
    }
}