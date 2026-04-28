<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Accesor tipado a variables de entorno.
 *
 * El bootstrap ya populó $_ENV mediante vlucas/phpdotenv. Esta clase es la
 * ÚNICA vía permitida para leer config en el resto del código — nada de
 * tocar getenv() / $_ENV directamente desde controllers o services.
 *
 * Lee cada vez (sin caché): phpdotenv ya hizo el trabajo una sola vez al
 * inicio del request.
 */
final class Env
{
    /**
     * Lee una variable como string. Si no existe y no se da default, lanza.
     */
    public static function string(string $key, ?string $default = null): string
    {
        $value = self::raw($key);
        if ($value === null) {
            if ($default === null) {
                throw new RuntimeException("Variable de entorno requerida ausente: {$key}");
            }
            return $default;
        }
        return $value;
    }

    /**
     * Lee una variable como int. Lanza si el valor no es numérico.
     */
    public static function int(string $key, ?int $default = null): int
    {
        $value = self::raw($key);
        if ($value === null || $value === '') {
            if ($default === null) {
                throw new RuntimeException("Variable de entorno requerida ausente: {$key}");
            }
            return $default;
        }
        if (!preg_match('/^-?\d+$/', $value)) {
            throw new RuntimeException("Variable de entorno {$key} no es entero: {$value}");
        }
        return (int) $value;
    }

    /**
     * Lee una variable como bool. Acepta: true|false|1|0|yes|no|on|off (case-insensitive).
     */
    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::raw($key);
        if ($value === null || $value === '') {
            return $default;
        }
        return match (strtolower($value)) {
            'true', '1', 'yes', 'on'   => true,
            'false', '0', 'no', 'off'  => false,
            default => throw new RuntimeException("Variable de entorno {$key} no es booleana: {$value}"),
        };
    }

    /**
     * Igual que string() sin default — alias enfático para vars críticas
     * cuya ausencia debe romper el boot.
     */
    public static function require(string $key): string
    {
        return self::string($key);
    }

    /**
     * Lee la fuente cruda. Prioriza $_ENV (poblado por phpdotenv en boot),
     * cae a getenv() como fallback. Devuelve null si no existe.
     */
    private static function raw(string $key): ?string
    {
        if (array_key_exists($key, $_ENV)) {
            $v = $_ENV[$key];
            return $v === false ? null : (string) $v;
        }
        $v = getenv($key);
        return $v === false ? null : $v;
    }
}
