<?php

namespace Src\Academico\Dominio;

/** Parámetros institucionales de Académico (`parametros_institucionales`, RNF-09): las reglas de cálculo no llevan constantes. */
interface RepositorioDeParametros
{
    /** @throws \LogicException si el parámetro `riesgo_academico` vigente no existe: es un fallo de datos de fábrica */
    public function riesgoAcademico(): ParametrosDeRiesgo;
}
