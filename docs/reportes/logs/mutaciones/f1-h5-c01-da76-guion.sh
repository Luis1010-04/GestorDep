set -u
rm -rf /tmp/mut /tmp/docs; cp -a /var/www/html /tmp/mut; ln -s /var/www/docs /tmp/docs
cd /tmp/mut
f=src/Salud/Aplicacion/ConfirmarAviso.php
cp $f /tmp/orig.php
correr() { php vendor/bin/pest tests/Feature/Salud/ConfirmacionYEstadoTest.php --filter='DA-76|DA-45' 2>&1 | sed 's/\x1b\[[0-9;]*m//g' | grep -E "✓|⨯|Tests:"; }
mutar() {
  echo "== $1"
  cp /tmp/orig.php $f
  php -r '$p=$argv[1]; $c=file_get_contents($p); $n=substr_count($c,$argv[2]); file_put_contents($p,str_replace($argv[2],$argv[3],$c)); echo "reemplazos: $n\n";' $f "$2" "$3"
  correr
}
echo "== control (sin mutar)"; correr
mutar "M1 sin el filtro del registrante" '|| ! $lesion->esDe($autor) ' ''
mutar "M2 sin el filtro de lesión ya cerrada" ' || $lesion->fechaAlta() !== null' ''
mutar "M3 día UTC en lugar de HoyDeNegocio" '$this->hoyDeNegocio->hoy()->format' '$instante->format'
mutar "M4 cierra también sin apto" 'if ($tipo === TipoDeEstado::Apto) {' 'if (true) {'
mutar "M5 no cierra nunca" '$this->cerrarLesionPropia($vigente, $autor, $instante);' ''
cp /tmp/orig.php $f
echo "== control final"; correr
cd /; rm -rf /tmp/mut /tmp/docs /tmp/orig.php
ls -d /tmp/mut /tmp/docs /tmp/orig.php 2>&1
