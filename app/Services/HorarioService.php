<?php

namespace App\Services;

use App\Models\Horario;
use App\Models\Medico;
use App\Enums\DiaSemana;
use App\Models\Cita;
use App\Enums\EstadoCita;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Collection;

class HorarioService
{
    /**
     * Obtener todos los horarios de un médico para que pueda verlos (médico)
     */
    public function obtenerHorarioMedico(int $medicoId): Collection
    {
        return Horario::where('id_medico', $medicoId)
            ->orderBy('dia_semana')
            ->orderBy('hora_inicio')
            ->get();
    }

    /**
     * Obtener todos los horarios (admin)
     */
    public function obtenerTodosLosHorarios(): Collection
    {
        return Horario::with('medico.usuario')
            ->orderBy('id_medico')
            ->orderBy('dia_semana')
            ->orderBy('hora_inicio')
            ->get();
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
     * Actualizar un horario con Route Model Binding
     */
    public function actualizarHorario(Horario $horario, array $datos): Horario
    {
        // Si intentan cambiar el médico, comprobamos que exista de verdad
        if (isset($datos['id_medico']) && $datos['id_medico'] != $horario->id_medico) {
            if (!\App\Models\Medico::where('id', $datos['id_medico'])->exists()) {
                throw new NotFoundHttpException('Médico no encontrado');
            }
        }
        
        $dia = $datos['dia_semana'] ?? $horario->dia_semana->value;
        $horaInicio = $datos['hora_inicio'] ?? $horario->hora_inicio->format('H:i');
        $horaFin = $datos['hora_fin'] ?? $horario->hora_fin->format('H:i');

        if ($horaInicio >= $horaFin) {
            throw new \InvalidArgumentException('La hora de inicio debe ser menor que la hora de fin');
        }

        // Definimos aquí el ID del médico que vamos a usar en todo el proceso
        $idMedicoAUsar = $datos['id_medico'] ?? $horario->id_medico;

        return DB::transaction(function () use ($horario, $datos, $idMedicoAUsar, $dia, $horaInicio, $horaFin) {
            $solapa = $this->existeSolape(
                $idMedicoAUsar,
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
     * Eliminar un horario con Route Model Binding.
     * Solo permite borrar si el médico NO tiene citas activas
     * asociadas a ese horario (mismo día de la semana + dentro del rango de horas).
     */
    public function eliminarHorario(Horario $horario): void
    {
        DB::transaction(function () use ($horario) {
            $tieneCitas = Cita::where('id_medico', $horario->id_medico)
                ->where('estado', EstadoCita::ACTIVA)
                ->where('fecha', '>=', now()->toDateString())
                ->whereRaw('DAYOFWEEK(fecha) = ?', [$horario->dia_semana->numeroMysql()])
                ->whereTime('hora', '>=', $horario->hora_inicio->format('H:i'))
                ->whereTime('hora', '<', $horario->hora_fin->format('H:i'))
                ->lockForUpdate()
                ->exists();

            if ($tieneCitas) {
                throw new UnprocessableEntityHttpException(
                    'No se puede eliminar el horario porque existen citas activas programadas en esta franja.'
                );
            }

            $horario->delete();

            Log::info('Horario eliminado', [
                'horario_id' => $horario->id,
                'medico_id'  => $horario->id_medico,
            ]);
        });
    }

    /**
     * Comprueba si una franja horaria se solapa con otra ya existente
     * para ese médico y ese día.
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