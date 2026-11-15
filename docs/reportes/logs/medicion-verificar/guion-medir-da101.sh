#!/usr/bin/env bash
# Mide corridas completas de `make verificar` para DA-101 (Pest en paralelo), una tras otra y sin reintentos.
# Uso, desde la raíz del repositorio en Git Bash: bash docs/reportes/logs/medicion-verificar/guion-medir-da101.sh <etiqueta> <corridas>
# Cada corrida deja su log completo; el resumen de la serie se agrega a resumen-da101-<etiqueta>-<sello>.log.
# Mientras corre no se ejecuta ningún otro comando.
set -u
etiqueta=$1
corridas=$2
dir=docs/reportes/logs/medicion-verificar
sello=$(date +%Y%m%d-%H%M)
resumen=$dir/resumen-da101-$etiqueta-$sello.log
carga() { docker compose exec -T app cat /proc/loadavg </dev/null 2>/dev/null; }
cpu() { powershell -NoProfile -Command "(Get-CimInstance Win32_Processor | Measure-Object LoadPercentage -Average).Average" 2>/dev/null | tr -d '\r'; }

echo "# DA-101 serie $etiqueta | commit $(git rev-parse --short HEAD) | $(date -Is)" > "$resumen"
echo "# paso de Pest: $(grep -o 'vendor/bin/pest[^"]*' backend/composer.json | head -1)" >> "$resumen"

for i in $(seq 1 "$corridas"); do
    log=$dir/da101-$etiqueta-$i-$sello.log
    c0=$(carga); u0=$(cpu); t0=$(date +%s)
    {
        echo "commit base: $(git rev-parse --short HEAD) | make verificar | serie $etiqueta, corrida $i | $(date -Is)"
        echo "# loadavg VM antes: $c0 | CPU anfitrion antes: $u0 %"
        MSYS_NO_PATHCONV=1 docker compose exec -T app bash bin/con-candado.sh composer verificar </dev/null 2>&1
        cod=$?
        t1=$(date +%s); c1=$(carga); u1=$(cpu)
        echo "# loadavg VM despues: $c1 | CPU anfitrion despues: $u1 %"
        echo "codigo: $cod | segundos: $(( t1 - t0 ))"
    } > "$log"
    pest=$(grep -aoE 'Duration:[^0-9]*[0-9.]+s' "$log" | grep -oE '[0-9.]+s' | tail -1)
    pruebas=$(grep -aoE '[0-9]+ passed[^(]*\([0-9]+ assertions\)' "$log" | sed 's/\x1b\[[0-9;]*m//g' | tail -1)
    capas=$(grep -aE '^(Dominio|Aplicación|Infraestructura y HTTP)' "$log" | awk '{printf "%s ", $0}' | tr -s ' ')
    echo "corrida=$i codigo=$cod total=$(( t1 - t0 ))s pest=$pest pruebas=[$pruebas] capas=[$capas] carga=[$c0 -> $c1] cpu=[$u0 -> $u1]" >> "$resumen"
    [ "$cod" -eq 0 ] || { echo "# la corrida $i fallo; la serie se detiene" >> "$resumen"; break; }
done
cat "$resumen"
