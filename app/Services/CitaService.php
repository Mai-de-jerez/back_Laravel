<?php

namespace App\Services;

use App\Models\Cita;
use App\Models\Horario;
use App\Models\Medico;
use App\Models\User;
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
     * Busca el día más próximo (desde hoy) en el que el médico tenga
     * al menos un hueco libre de 15 min, teniendo en cuenta su horario
     * semanal y las citas activas ya reservadas.
     *
     * @param int $idMedico
     * @param int $diasMaximoBusqueda Cuántos días hacia adelante busca antes de rendirse
     * @return array ['fecha' => string|null, 'huecos' => array]
     */
    public function obtenerProximoDiaConHuecos(int $idMedico, int $diasMaximoBusqueda = 60): array
    {
        // Traemos TODOS los horarios del médico una sola vez (antes: 1 query por día)
        $horariosDelMedico = Horario::where('id_medico', $idMedico)->get();

        if ($horariosDelMedico->isEmpty()) {
            return ['fecha' => null, 'huecos' => []];
        }

        $fecha = Carbon::today();

        for ($i = 0; $i <= $diasMaximoBusqueda; $i++) {

            // 1. Filtrar en PHP (sin query) los horarios que aplican a este día de la semana
            $diaSemanaHoy = DiaSemana::fromFecha($fecha)->value;
            $horarios = $horariosDelMedico->filter(
                fn($h) => $h->dia_semana->value === $diaSemanaHoy
            );

            if ($horarios->isEmpty()) {
                $fecha->addDay();
                continue;
            }

            // 2. Sacar las citas ya ocupadas ese día (esto sí debe ser por día, cambia cada vez)
            $citasOcupadas = Cita::where('id_medico', $idMedico)
                ->where('fecha', $fecha->toDateString())
                ->where('estado', EstadoCita::ACTIVA)
                ->pluck('hora')
                ->map(fn($h) => Carbon::parse($h)->format('H:i'))
                ->toArray();

            // 3. Trocear el horario en bloques de 15 min y descartar los ocupados
            $huecos = [];

            foreach ($horarios as $h) {
                $inicio = Carbon::parse($h->hora_inicio);
                $fin = Carbon::parse($h->hora_fin);

                while ($inicio->copy()->addMinutes(15)->lte($fin)) {
                    $horaF = $inicio->format('H:i');
                    if (!in_array($horaF, $citasOcupadas)) {
                        $huecos[] = $horaF;
                    }
                    $inicio->addMinutes(15);
                }
            }

            sort($huecos);

            // 4. Si este día tiene huecos, ya está, devolvemos
            if (!empty($huecos)) {
                return [
                    'fecha' => $fecha->toDateString(),
                    'huecos' => $huecos,
                ];
            }

            // 5. Si no, probamos el día siguiente
            $fecha->addDay();
        }

        return ['fecha' => null, 'huecos' => []];
    }

    /**
     * Obtener las citas del usuario logueado (médico o paciente)
     */
    public function obtenerMisCitas(User $usuario, array $filtros = []): array
    {
        if ($usuario->medico) {
            $filtros['id_medico'] = $usuario->medico->id;
        } elseif ($usuario->paciente) {
            $filtros['id_paciente'] = $usuario->paciente->id;
        } else {
            throw new NotFoundHttpException('El usuario no tiene un perfil asociado para consultar citas');
        }

        return $this->obtenerTodasLasCitas($filtros);
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
     * Actualizar una cita existente
     */
    public function actualizarCita(Cita $cita, array $datos): Cita
    {
        if (isset($datos['id_medico']) && $datos['id_medico'] != $cita->id_medico) {
            if (!Medico::where('id', $datos['id_medico'])->exists()) {
                throw new NotFoundHttpException('Médico no encontrado');
            }
        }

        if (isset($datos['id_paciente']) && $datos['id_paciente'] != $cita->id_paciente) {
            if (!Paciente::where('id', $datos['id_paciente'])->exists()) {
                throw new NotFoundHttpException('Paciente no encontrado');
            }
        }

        $medicoId = $datos['id_medico'] ?? $cita->id_medico;
        $fecha = isset($datos['fecha']) ? Carbon::parse($datos['fecha']) : Carbon::parse($cita->fecha);
        $horaInicio = $datos['hora'] ?? $cita->hora;
        $horaFin = Carbon::parse($horaInicio)->addMinutes(15)->format('H:i');
        $diaSemana = DiaSemana::fromFecha($fecha);

        return DB::transaction(function () use ($cita, $datos, $medicoId, $fecha, $horaInicio, $horaFin, $diaSemana) {
            
            // Si cambian elementos de tiempo o médico, revalidamos disponibilidad y solapes
            if (isset($datos['fecha']) || isset($datos['hora']) || isset($datos['id_medico'])) {
                if (!$this->estaDentroDeHorario($medicoId, $diaSemana, $horaInicio, $horaFin)) {
                    throw new \InvalidArgumentException(
                        'El médico no tiene horario disponible en esa fecha y hora'
                    );
                }

                if ($this->existeSolapeCita($medicoId, $fecha->toDateString(), $horaInicio, $horaFin, $cita->id, true)) {
                    throw new \InvalidArgumentException(
                        'El médico ya tiene otra cita activa en ese horario'
                    );
                }
            }

            $cita->update($datos);

            Log::info('Cita actualizada', [
                'cita_id' => $cita->id,
                'medico_id' => $medicoId,
                'datos_actualizados' => $datos,
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
     * Solape bidireccional: la nueva cita y la existente se pisan si el inicio de una
     * es anterior al fin de la otra, en ambos sentidos.
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
            ->whereRaw("ADDTIME(hora, '00:15:00') > ?", [$horaInicio]);

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