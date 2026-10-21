<?php

use App\Providers\AppServiceProvider;
use Src\Academico\Infraestructura\AcademicoServiceProvider;
use Src\Alertas\Infraestructura\AlertasServiceProvider;
use Src\Compartido\Infraestructura\CompartidoServiceProvider;
use Src\Competencia\Infraestructura\CompetenciaServiceProvider;
use Src\CondicionFisica\Infraestructura\CondicionFisicaServiceProvider;
use Src\Consultas\Infraestructura\ConsultasServiceProvider;
use Src\Identidad\Infraestructura\IdentidadServiceProvider;
use Src\Plantel\Infraestructura\PlantelServiceProvider;
use Src\Rendimiento\Infraestructura\RendimientoServiceProvider;
use Src\Salud\Infraestructura\SaludServiceProvider;

return [
    AppServiceProvider::class,
    CompartidoServiceProvider::class,
    IdentidadServiceProvider::class,
    PlantelServiceProvider::class,
    AcademicoServiceProvider::class,
    CompetenciaServiceProvider::class,
    RendimientoServiceProvider::class,
    CondicionFisicaServiceProvider::class,
    SaludServiceProvider::class,
    AlertasServiceProvider::class,
    ConsultasServiceProvider::class,
];
