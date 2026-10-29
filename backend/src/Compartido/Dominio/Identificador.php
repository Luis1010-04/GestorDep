<?php

namespace Src\Compartido\Dominio;

use InvalidArgumentException;

/**
 * Identificador UUID v7 (RFC 9562): 48 bits de marca de tiempo en milisegundos,
 * version 7, variante RFC 4122 y bits aleatorios.
 */
final class Identificador
{
    private const PATRON = '/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/';

    private function __construct(private readonly string $valor) {}

    public static function generar(): self
    {
        $milisegundos = (int) (microtime(true) * 1000);
        $bytes = substr(pack('J', $milisegundos), 2).random_bytes(10);

        $bytes[6] = chr((ord($bytes[6]) & 0x0F) | 0x70);
        $bytes[8] = chr((ord($bytes[8]) & 0x3F) | 0x80);

        $hex = bin2hex($bytes);

        return new self(sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12),
        ));
    }

    public static function desdeTexto(string $texto): self
    {
        $valor = strtolower($texto);

        if (preg_match(self::PATRON, $valor) !== 1) {
            throw new InvalidArgumentException('identificador_invalido');
        }

        return new self($valor);
    }

    public function valor(): string
    {
        return $this->valor;
    }

    public function igualA(self $otro): bool
    {
        return $this->valor === $otro->valor;
    }
}
