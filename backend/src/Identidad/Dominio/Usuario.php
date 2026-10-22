<?php

namespace Src\Identidad\Dominio;

use DateTimeImmutable;
use Src\Compartido\Dominio\Identificador;

/** Usuario del sistema (docente o entrenador). Las reglas de bloqueo de RNF-10 reciben la hora y sus parámetros. */
final class Usuario
{
    /**
     * @param  list<string>  $roles  códigos de rol
     */
    private function __construct(
        private readonly Identificador $id,
        private readonly string $nombre,
        private readonly string $correo,
        private readonly string $contrasenaHash,
        private readonly bool $activo,
        private readonly array $roles,
        private int $intentosFallidos,
        private ?DateTimeImmutable $bloqueadoHasta,
    ) {}

    /**
     * @param  list<string>  $roles
     */
    public static function crear(Identificador $id, string $nombre, string $correo, string $contrasenaHash, array $roles): self
    {
        return new self($id, $nombre, $correo, $contrasenaHash, true, $roles, 0, null);
    }

    /**
     * @param  list<string>  $roles
     */
    public static function reconstituir(
        Identificador $id,
        string $nombre,
        string $correo,
        string $contrasenaHash,
        bool $activo,
        array $roles,
        int $intentosFallidos,
        ?DateTimeImmutable $bloqueadoHasta,
    ): self {
        return new self($id, $nombre, $correo, $contrasenaHash, $activo, $roles, $intentosFallidos, $bloqueadoHasta);
    }

    public function estaBloqueado(DateTimeImmutable $ahora): bool
    {
        return $this->bloqueadoHasta !== null && $this->bloqueadoHasta > $ahora;
    }

    /** Un fallo con el bloqueo ya vencido reinicia el contador antes de contar. */
    public function registrarIntentoFallido(DateTimeImmutable $ahora, int $intentosMaximos, int $minutosDeBloqueo): void
    {
        if ($this->bloqueadoHasta !== null && ! $this->estaBloqueado($ahora)) {
            $this->intentosFallidos = 0;
            $this->bloqueadoHasta = null;
        }

        $this->intentosFallidos++;

        if ($this->intentosFallidos >= $intentosMaximos) {
            $this->bloqueadoHasta = $ahora->modify("+{$minutosDeBloqueo} minutes");
        }
    }

    public function registrarAcceso(): void
    {
        $this->intentosFallidos = 0;
        $this->bloqueadoHasta = null;
    }

    public function id(): Identificador
    {
        return $this->id;
    }

    public function nombre(): string
    {
        return $this->nombre;
    }

    public function correo(): string
    {
        return $this->correo;
    }

    public function contrasenaHash(): string
    {
        return $this->contrasenaHash;
    }

    public function activo(): bool
    {
        return $this->activo;
    }

    /** @return list<string> */
    public function roles(): array
    {
        return $this->roles;
    }

    public function intentosFallidos(): int
    {
        return $this->intentosFallidos;
    }

    public function bloqueadoHasta(): ?DateTimeImmutable
    {
        return $this->bloqueadoHasta;
    }
}
