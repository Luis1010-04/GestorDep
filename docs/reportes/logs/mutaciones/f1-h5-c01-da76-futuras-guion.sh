set -u
rm -rf /tmp/mut /tmp/docs; cp -a /var/www/html /tmp/mut; ln -s /var/www/docs /tmp/docs
cd /tmp/mut
f=src/Salud/Aplicacion/ConfirmarAviso.php
correr() { php vendor/bin/pest tests/Feature/Salud/ConfirmacionYEstadoTest.php --filter='DA-76|DA-45' 2>&1 | sed 's/\x1b\[[0-9;]*m//g' | grep -E "✓|⨯|Tests:"; }
echo "== control (sin mutar)"; correr
echo "== M6 sin la comprobación de inicio posterior a hoy"
php -r '$p=$argv[1]; $c=file_get_contents($p); $n=substr_count($c,$argv[2]); file_put_contents($p,str_replace($argv[2],$argv[3],$c)); echo "reemplazos: $n\n";' $f 'if ($lesion->fechaInicio() > $hoy) {' 'if (false) {'
correr
echo "== M7 comprobación con >= (no cerraría la lesión que empieza hoy)"
cp /var/www/html/$f $f
php -r '$p=$argv[1]; $c=file_get_contents($p); $n=substr_count($c,$argv[2]); file_put_contents($p,str_replace($argv[2],$argv[3],$c)); echo "reemplazos: $n\n";' $f 'if ($lesion->fechaInicio() > $hoy) {' 'if ($lesion->fechaInicio() >= $hoy) {'
correr
cp /var/www/html/$f $f
echo "== control final"; correr
cd /; rm -rf /tmp/mut /tmp/docs
ls -d /tmp/mut /tmp/docs 2>&1
