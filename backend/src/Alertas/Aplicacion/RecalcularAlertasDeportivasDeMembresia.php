<?php

namespace Src\Alertas\Aplicacion;

use DateTimeImmutable;
use DateTimeZone;
use Src\Alertas\Dominio\AcumulacionDeSanciones;
use Src\Alertas\Dominio\InasistenciaAEntrenamiento;
use Src\Alertas\Dominio\ResultadoDeEvaluacion;
use Src\Alertas\Dominio\RiesgoDeBajaDeportiva;
use Src\Alertas\Dominio\TarjetasDePartido;
use Src\Alertas\Dominio\TarjetasRegistradas;
use Src\Compartido\Aplicacion\EvaluacionesVigentesDelEquipo;
use Src\Compartido\Aplicacion\EvaluacionVigente;
use Src\Compartido\Aplicacion\FaltaDeEntrenamiento;
use Src\Compartido\Aplicacion\FaltasDeEntrenamiento;
use Src\Compartido\Aplicacion\InicioDePartidos;
use Src\Compartido\Aplicacion\TarjetasPorPartido;
use Src\Compartido\Aplicacion\TemporadaDeFecha;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\NumeroDecimal;

/**
 * Recalcula las dos alertas deportivas de un jugador en un equipo: primero la acumulación de sanciones (RN-09) y después
 * el riesgo de baja deportiva (RN-10), que usa el resultado de la primera. Cuenta los datos de todos los entrenadores del
 * equipo (DA-102, DA-103) y lee los demás contextos solo por puertos (DA-112).
 */
final class RecalcularAlertasDeportivasDeMembresia
{
    public function __construct(
        private readonly TarjetasPorPartido $tarjetas,
        private readonly InicioDePartidos $inicioDePartidos,
        private readonly TemporadaDeFecha $temporadas,
        private readonly FaltasDeEntrenamiento $faltas,
        private readonly EvaluacionesVigentesDelEquipo $evaluaciones,
        private readonly RepositorioDeParametrosDeAlertas $parametros,
        private readonly AplicarDecisionDeAlerta $aplicar,
    ) {}

    /** @param  DateTimeImmutable  $hoy  medianoche del día de negocio: la fecha de cálculo, tomada una vez por corrida */
    public function ejecutar(Identificador $jugador, Identificador $equipo, DateTimeImmutable $ahora, DateTimeImmutable $hoy): void
    {
        $parametros = $this->parametros->sanciones();
        $temporada = $this->temporadas->de($hoy)?->valor();
        $tarjetas = TarjetasDePartido::elMayorPorPartido($this->tarjetasRegistradas($jugador, $equipo, $hoy->getTimezone()));
        $inasistencias = array_map(
            fn (FaltaDeEntrenamiento $falta): InasistenciaAEntrenamiento => new InasistenciaAEntrenamiento($falta->fecha, $falta->justificada),
            $this->faltas->de($jugador, $equipo, $hoy->modify('-'.($parametros->faltasVentanaDias() - 1).' days'), $hoy),
        );

        $hayAcumulacion = AcumulacionDeSanciones::seCumple($tarjetas, $temporada, $inasistencias, $hoy, $parametros);
        $this->aplicar->ejecutar(
            TipoDeAlerta::AcumulacionDeSanciones,
            $jugador,
            $equipo,
            $hayAcumulacion,
            AcumulacionDeSanciones::conteos($tarjetas, $temporada, $inasistencias, $hoy, $parametros),
            $ahora,
        );

        $evaluaciones = $this->evaluaciones->de($jugador, $equipo);
        $resultado = ResultadoDeEvaluacion::delEquipo(array_map(fn (EvaluacionVigente $evaluacion): ResultadoDeEvaluacion => ResultadoDeEvaluacion::from($evaluacion->resultado), $evaluaciones));
        $this->aplicar->ejecutar(
            TipoDeAlerta::RiesgoDeBajaDeportiva,
            $jugador,
            $equipo,
            RiesgoDeBajaDeportiva::seCumple($resultado, $hayAcumulacion),
            ['resultado' => $resultado->value, 'promedio' => $this->promedioQueDecide($evaluaciones, $resultado), 'acumulacion_sanciones' => $hayAcumulacion],
            $ahora,
        );
    }

    /**
     * Cada fila de tarjetas con la temporada de su partido (DA-117): la que contiene la fecha de inicio del partido en la
     * zona de negocio. Un partido que ya no existe, o cuya fecha no cae en ninguna temporada, no puede sumar a una.
     *
     * @return list<TarjetasRegistradas>
     */
    private function tarjetasRegistradas(Identificador $jugador, Identificador $equipo, DateTimeZone $zona): array
    {
        $filas = $this->tarjetas->de($jugador, $equipo);
        $inicios = $this->inicioDePartidos->de(array_map(fn ($fila): Identificador => Identificador::desdeTexto($fila->partidoId), $filas));
        /** @var array<string, string|null> $temporadasPorDia */
        $temporadasPorDia = [];
        $registros = [];

        foreach ($filas as $fila) {
            if (! isset($inicios[$fila->partidoId])) {
                continue;
            }

            $dia = $inicios[$fila->partidoId]->setTimezone($zona)->setTime(0, 0);
            $clave = $dia->format('Y-m-d');
            $temporadasPorDia[$clave] ??= $this->temporadas->de($dia)?->valor();

            if ($temporadasPorDia[$clave] !== null) {
                $registros[] = new TarjetasRegistradas($fila->partidoId, $temporadasPorDia[$clave], $fila->amarillas, $fila->rojas);
            }
        }

        return $registros;
    }

    /**
     * El promedio de la causa (DA-116): el de la evaluación que decide el resultado. Con `no_pasa`, el menor entre las
     * `no_pasa`; con `pasa`, el mayor entre las `pasa`; con `datos_insuficientes`, ninguno.
     *
     * @param  list<EvaluacionVigente>  $evaluaciones
     */
    private function promedioQueDecide(array $evaluaciones, ResultadoDeEvaluacion $resultado): ?string
    {
        if ($resultado === ResultadoDeEvaluacion::DatosInsuficientes) {
            return null;
        }

        $elegido = null;
        foreach ($evaluaciones as $evaluacion) {
            if ($evaluacion->resultado !== $resultado->value) {
                continue;
            }

            $promedio = NumeroDecimal::desdeTexto((string) $evaluacion->promedio);
            if ($elegido === null || ($resultado === ResultadoDeEvaluacion::NoPasa ? $promedio->menorQue($elegido) : $promedio->mayorQue($elegido))) {
                $elegido = $promedio;
            }
        }

        return $elegido?->comoCadena();
    }
}
