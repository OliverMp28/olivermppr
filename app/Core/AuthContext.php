<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\User;
use RuntimeException;

/**
 * AuthContext — holder estático request-scoped del User autenticado.
 *
 * Lo setea AuthMiddleware tras verificar el JWT y resolver `vout_id` →
 * `User::findByVoutId`. Los controllers Bloque 7+ leen `AuthContext::user()`
 * sin re-parsear el JWT ni tocar `$_SESSION` (las API son stateless por
 * Bearer, no por sesión).
 *
 * Por qué estático y no DI: PHP-FPM aísla cada petición en su propio
 * proceso/worker, así que el estado estático se reinicia por request
 * automáticamente. No hay riesgo de leak entre requests.
 */
final class AuthContext
{
    private static ?User $user = null;

    public static function set(User $user): void
    {
        self::$user = $user;
    }

    /**
     * Devuelve el User autenticado o lanza si no hay (defensa contra olvidos
     * — un controller bajo AuthMiddleware siempre debería tener User aquí).
     */
    public static function user(): User
    {
        if (self::$user === null) {
            throw new RuntimeException('AuthContext::user() llamado sin usuario autenticado. ¿Falta AuthMiddleware en la ruta?');
        }
        return self::$user;
    }

    public static function tryUser(): ?User
    {
        return self::$user;
    }

    public static function clear(): void
    {
        self::$user = null;
    }
}
