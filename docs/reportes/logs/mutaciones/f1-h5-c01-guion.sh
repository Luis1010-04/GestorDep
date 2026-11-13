set -u
rm -rf /tmp/mut /tmp/docs; cp -a /var/www/html /tmp/mut; ln -s /var/www/docs /tmp/docs
cd /tmp/mut
filtro='DA-79: buscarParaActualizar bloquea|DA-79: las lecturas para actualizar'
pruebas="tests/Integration/Salud/RepositorioDeAvisosTest.php tests/Integration/Salud/RepositorioDeEstadosYLesionesTest.php"
echo "== control (sin mutar)"
php vendor/bin/pest $pruebas --filter="$filtro" 2>&1 | sed 's/\x1b\[[0-9;]*m//g' | grep -E "✓|⨯|Tests:"
for f in RepositorioDeAvisosSql RepositorioDeEstadosSql RepositorioDeLesionesSql; do
  cp src/Salud/Infraestructura/$f.php /tmp/orig.php
  php -r '$p=$argv[1]; $c=file_get_contents($p); $n=substr_count($c,"->lockForUpdate()"); file_put_contents($p,str_replace("->lockForUpdate()","",$c)); echo "quitados: $n\n";' src/Salud/Infraestructura/$f.php
  echo "== sin lockForUpdate en $f"
  php vendor/bin/pest $pruebas --filter="$filtro" 2>&1 | sed 's/\x1b\[[0-9;]*m//g' | grep -E "✓|⨯|Tests:"
  cp /tmp/orig.php src/Salud/Infraestructura/$f.php
done
echo "== sin el catch en RepositorioDeAvisosSql"
cp src/Salud/Infraestructura/RepositorioDeAvisosSql.php /tmp/orig.php
php -r '$p=$argv[1]; $c=file_get_contents($p); $c=str_replace("catch (UniqueConstraintViolationException)","catch (\\LogicException)",$c,$n); file_put_contents($p,$c); echo "cambiados: $n\n";' src/Salud/Infraestructura/RepositorioDeAvisosSql.php
php vendor/bin/pest tests/Integration/Salud/RepositorioDeAvisosTest.php --filter='choca con un índice' 2>&1 | sed 's/\x1b\[[0-9;]*m//g' | grep -E "✓|⨯|Tests:"
cp /tmp/orig.php src/Salud/Infraestructura/RepositorioDeAvisosSql.php
echo "== control final"
php vendor/bin/pest $pruebas --filter="$filtro|choca con un índice" 2>&1 | sed 's/\x1b\[[0-9;]*m//g' | grep -E "Tests:"
cd /; rm -rf /tmp/mut /tmp/docs /tmp/orig.php
