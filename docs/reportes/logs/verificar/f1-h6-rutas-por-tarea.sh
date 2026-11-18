#!/usr/bin/env bash
# Archivos tocados por los commits de cada tarea de H6 frente a sus rutas permitidas (docs/hitos/f1-h6.md §8; T00: plan-da101 §7).
# Uso: bash rutas-por-tarea.sh  (desde la raiz del repositorio)
cd "$(git rev-parse --show-toplevel)" || exit 1

declare -A PERMITIDAS
# Los reportes y sus logs (docs/reportes/**) se dan por permitidos en todas las tareas: §0 pide un reporte f1-h6-tNN.md por tarea.
REP='docs/reportes/**'
PERMITIDAS[T00]="backend/composer.json backend/tests/TestCase.php backend/tests/Feature/EntornoDePruebasTest.php backend/tests/Unit/Herramientas/** backend/tests/Ayudantes/DominioDePrueba.php backend/tests/Pest.php backend/tests/Unit/Academico/ParametrosDeRiesgoTest.php backend/tests/Unit/Compartido/NumeroDecimalTest.php backend/docker/db/init/** Makefile .github/workflows/verificar.yml backend/bin/con-candado.sh docs/estrategia-pruebas.md docs/decisiones/registro-decisiones.md $REP"
PERMITIDAS[T01]="backend/database/migrations/alertas/** backend/database/seeders/ParametrosInstitucionalesSeeder.php backend/database/seeders/DatabaseSeeder.php backend/tests/Integration/Alertas/** backend/tests/Integration/Academico/MigracionesDeAcademicoTest.php $REP"
PERMITIDAS[T02]="backend/src/Alertas/Dominio/** backend/tests/Unit/Alertas/** $REP"
PERMITIDAS[T03]="backend/src/Alertas/Aplicacion/** backend/src/Alertas/Infraestructura/** backend/src/Alertas/Dominio/AcumulacionDeSanciones.php backend/src/Academico/Infraestructura/** backend/src/Plantel/Infraestructura/** backend/src/Compartido/Aplicacion/** backend/src/Rendimiento/Infraestructura/** backend/src/Competencia/Infraestructura/** backend/routes/console.php backend/tests/Feature/Alertas/** backend/tests/Ayudantes/MundoDeAlertas.php backend/tests/Unit/Alertas/** $REP"
PERMITIDAS[T04]="backend/src/Alertas/** backend/src/Consultas/** backend/app/Http/** backend/routes/api.php backend/lang/es/** docs/api/openapi.yaml backend/tests/** $REP"
PERMITIDAS[T05]="backend/src/Consultas/** backend/app/Http/** backend/routes/api.php docs/api/openapi.yaml backend/tests/** $REP"
PERMITIDAS[T06]="backend/src/Competencia/Aplicacion/** backend/src/Consultas/** backend/app/Http/** backend/routes/api.php docs/api/openapi.yaml backend/tests/** $REP"
PERMITIDAS[T07]="backend/src/Consultas/** backend/app/Http/** backend/routes/api.php docs/api/openapi.yaml backend/tests/** backend/src/Compartido/Aplicacion/** backend/src/Academico/Infraestructura/** backend/src/Competencia/Infraestructura/** $REP"
PERMITIDAS[T08]="backend/tests/Feature/Seguridad/** backend/tests/Feature/Alertas/** backend/tests/Feature/Consultas/** $REP"
PERMITIDAS[T09]="scripts/humo/humo-h6.sh Makefile backend/tests/Unit/Herramientas/** docs/trazabilidad.md docs/decisiones/registro-decisiones.md $REP"

coincide() { # <ruta> <patrones...>
    local ruta=$1 p re; shift
    for p in "$@"; do
        re="^$(printf '%s' "$p" | sed -e 's/[.]/\\./g' -e 's/\*\*/.*/g')\$"
        [[ $ruta =~ $re ]] && return 0
    done
    return 1
}

for t in T00 T01 T02 T03 T04 T05 T06 T07 T08 T09; do
    commits=$(git log --reverse --format=%h --grep="^F1-H6-$t:")
    n=$(printf '%s\n' "$commits" | grep -c .)
    archivos=$(for c in $commits; do git show --name-only --format= "$c"; done | sort -u | grep -v '^$')
    total=$(printf '%s\n' "$archivos" | grep -c .)
    fuera=""
    read -ra pats <<< "${PERMITIDAS[$t]}"
    while IFS= read -r f; do
        [ -z "$f" ] && continue
        coincide "$f" "${pats[@]}" || fuera+="$f"$'\n'
    done <<< "$archivos"
    primero=$(printf '%s\n' "$commits" | head -1); previo=$(git rev-parse --short "$primero~1")
    printf '== %s: %s commits (%s a %s), anterior al primero: %s; %s archivos distintos\n' "$t" "$n" "$primero" "$(printf '%s\n' "$commits" | tail -1)" "$previo" "$total"
    if [ -n "$fuera" ]; then printf '   FUERA DE LAS RUTAS PERMITIDAS:\n'; printf '%s' "$fuera" | sed 's/^/     /'; else echo "   sin discrepancias"; fi
done
