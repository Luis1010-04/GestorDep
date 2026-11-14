#!/usr/bin/env bash
# Mide cada etapa de `composer verificar` (la receta de `make verificar`) por separado y en serie (DA-57, solo medición).
# Se corre desde la raíz del repositorio en Git Bash. No reintenta: si una etapa falla, se detiene.
set -u
dir=docs/reportes/logs/medicion-verificar
sello=$(date +%Y%m%d-%H%M)
resumen=$dir/resumen-$sello.log
carga() { docker compose exec -T app cat /proc/loadavg </dev/null 2>/dev/null; }

etapa() {
    local n=$1 nombre=$2; shift 2
    local log=$dir/$n-$nombre-$sello.log
    local c0 t0 t1 cod c1
    c0=$(carga); t0=$(date +%s)
    {
        echo "# etapa $n $nombre | commit $(git rev-parse --short HEAD)"
        echo "# inicio $t0 ($(date -u +%H:%M:%SZ)) | loadavg VM: $c0"
        echo "# comando: $*"
        MSYS_NO_PATHCONV=1 docker compose exec -T app bash bin/con-candado.sh "$@" </dev/null 2>&1
        cod=$?
        t1=$(date +%s); c1=$(carga)
        echo "# fin $t1 ($(date -u +%H:%M:%SZ)) | loadavg VM: $c1"
        echo "# codigo: $cod | duracion: $(( t1 - t0 )) s"
        echo "$n $nombre codigo=$cod duracion=$(( t1 - t0 ))s inicio=$t0 fin=$t1 carga_antes=[$c0] carga_despues=[$c1]" >> "$resumen"
        return $cod
    } > "$log"
}

total0=$(date +%s)
echo "# medicion de make verificar por etapas | commit $(git rev-parse --short HEAD) | inicio $total0" > "$resumen"
etapa 01 pint php vendor/bin/pint --test \
 && etapa 02 phpstan php vendor/bin/phpstan analyse --memory-limit=1G --no-progress \
 && etapa 03 pest php -d memory_limit=1G -d pcov.directory=. vendor/bin/pest --coverage --coverage-html=build/coverage --coverage-clover=build/coverage/clover.xml \
 && etapa 04 cobertura php bin/verificar-cobertura.php build/coverage/clover.xml \
 && etapa 05 audit composer audit
final=$?
total1=$(date +%s)
echo "# total: $(( total1 - total0 )) s | codigo final: $final" >> "$resumen"
cat "$resumen"
