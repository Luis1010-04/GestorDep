#!/usr/bin/env bash
# Mutaciones de T07 (desempate por id de las alertas y de la convocatoria: las pruebas que pasaron a la primera). Corre dentro del contenedor `app`, tras tomar el candado
# de la base de pruebas:
#   docker compose exec -T app bash bin/con-candado.sh bash /var/www/docs/reportes/logs/mutaciones/f1-h7-t07-guion.sh <lote 1> [solo-reemplazos]
# La copia vive en /tmp/mut del contenedor, no en el árbol del repositorio: /var/www/html (el backend, montado desde ./backend)
# solo se lee con `cp -a`, y /var/www/docs es de solo lectura. Al terminar (también si falla) se borra la copia. Mismo
# esquema que f1-h7-t05-filtros-guion.sh.
set -u

LOTE="${1:?falta el lote (1)}"
OPCION="${2:-}"
DIRECTORIO="$(cd "$(dirname "$0")" && pwd)"

limpiar() {
    cd / && rm -rf /tmp/mut /tmp/docs
}
trap limpiar EXIT

rm -rf /tmp/mut /tmp/docs
cp -a /var/www/html /tmp/mut
ln -s /var/www/docs /tmp/docs
cd /tmp/mut || exit 2

php "$DIRECTORIO/f1-h7-t07-mutaciones.php" "$LOTE" $OPCION
CODIGO=$?

limpiar
trap - EXIT
ls -d /tmp/mut /tmp/docs 2>&1
exit $CODIGO
