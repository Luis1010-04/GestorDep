#!/usr/bin/env bash
# Mutación de filtros de H6 (T08). Corre dentro del contenedor `app`, tras tomar el candado de la base de pruebas:
#   docker compose exec -T app bash bin/con-candado.sh bash /var/www/docs/reportes/logs/mutaciones/f1-h6-t08-filtros-guion.sh <lote 1|2|3> [solo-reemplazos]
# La copia vive en /tmp del contenedor, no en el árbol del repositorio: /var/www/html (el backend, montado desde ./backend) solo se
# lee con `cp -a`, y /var/www/docs es de solo lectura. Al terminar (también si falla) se borra la copia.
set -u

LOTE="${1:?falta el lote (1, 2 o 3)}"
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

php "$DIRECTORIO/f1-h6-t08-filtros-mutaciones.php" "$LOTE" $OPCION
CODIGO=$?

limpiar
trap - EXIT
ls -d /tmp/mut /tmp/docs 2>&1
exit $CODIGO
