#!/usr/bin/env bash
# Candado y vigilante de las corridas que usan la base de pruebas `gestordep_pruebas` (PT-07).
#
# Uso, desde el Makefile: docker compose exec -T app bash bin/con-candado.sh <comando>
# La receta es un solo comando, sin sintaxis de shell, para que funcione en cmd.exe, PowerShell y Git Bash.
#
# - Candado: dos corridas a la vez (verificar, pruebas, mutacion) se rompen el esquema entre si. Si otra corrida tiene
#   el candado, esta termina en el acto con el codigo 75. El candado vive en /tmp del contenedor, no en el montaje de
#   Windows, y lo heredan todos los procesos del comando: sigue tomado mientras viva cualquiera de ellos.
# - Vigilante: Docker no termina el proceso interno cuando muere el cliente de `docker compose exec`, pero la entrada
#   estandar que reenvia el cliente recibe EOF. Si el EOF llega en los primeros 2 s, la entrada estaba cerrada desde
#   el principio (entrada nula): el vigilante se desactiva y lo avisa. Si llega despues, el cliente murio y el vigilante
#   termina el grupo de procesos del comando. Una consola sin datos no envia EOF y no mata la corrida.
# - Tope: `timeout` corta el comando a los 20 minutos, aunque el vigilante no actue.
set -u

exec 9>/tmp/gestordep-pruebas.lock
if ! flock -n 9; then
    echo "Otra corrida de verificar, pruebas o mutacion usa la base de pruebas en este contenedor; espera a que termine." >&2
    exit 75
fi

# El comando corre en segundo plano con la entrada en /dev/null; el vigilante lee la entrada por el descriptor 3.
exec 3<&0
setsid timeout --kill-after=10s 20m "$@" &
grupo=$!

(
    inicio=${EPOCHREALTIME/./}
    while IFS= read -r -u 3 _; do
        :
    done
    if (( ${EPOCHREALTIME/./} - inicio < 2000000 )); then
        echo "Entrada estandar cerrada: el vigilante queda desactivado; protegen el candado y el tope de 20 min." >&2
        exit 0
    fi
    echo "Se perdio el cliente de docker compose exec; se termina la corrida." >&2
    kill -TERM -- "-$grupo" 2>/dev/null || exit 0
    sleep 10
    kill -KILL -- "-$grupo" 2>/dev/null
) 9>&- &
vigilante=$!

wait "$grupo"
codigo=$?

kill "$vigilante" 2>/dev/null
# Ningun proceso del comando sobrevive a la corrida, aunque haya terminado bien.
kill -KILL -- "-$grupo" 2>/dev/null

if [ "$codigo" -eq 124 ]; then
    echo "La corrida supero el tope de 20 minutos y se termino." >&2
fi

exit "$codigo"
