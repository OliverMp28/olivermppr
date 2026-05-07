<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\AuthContext;
use App\Core\Request;
use App\Core\Response;
use App\Models\User;
use App\Services\JwtVerifier;
use Closure;

/**
 * SoftAuthMiddleware — auth opcional.
 *
 * Si llega `Authorization: Bearer <jwt>` válido, populariza `AuthContext`
 * con el User correspondiente. Si NO llega, o si falla la validación,
 * deja `AuthContext` vacío y deja pasar la request igualmente.
 *
 * Caso de uso: endpoints que se comportan distinto según haya o no sesión.
 * Ejemplo: `GET /api/levels/{id}` — público devuelve 404 a niveles privados,
 * pero al autor sí le devuelve 200 con su row.
 *
 * Diferencia con AuthMiddleware: este NUNCA devuelve 401. El endpoint
 * decide la respuesta basándose en `AuthContext::tryUser()`. AuthMiddleware
 * es para rutas donde la auth es obligatoria.
 */
final class SoftAuthMiddleware
{
    public function __construct(
        private readonly ?JwtVerifier $verifier = null,
    ) {
    }

    public function __invoke(Request $req, Closure $next): Response
    {
        $token = $req->bearerToken();
        if ($token === null) {
            return $next($req);
        }

        try {
            $claims = ($this->verifier ?? JwtVerifier::fromEnv())->verify($token);
        } catch (\Throwable) {
            // Bearer inválido (firma mala, expirado): tratamos como anónimo.
            // No 401 — el endpoint público sigue siendo accesible.
            return $next($req);
        }

        $voutId = $claims['vout_id'] ?? null;
        if (!is_string($voutId) || $voutId === '') {
            return $next($req);
        }

        $user = User::findByVoutId($voutId);
        if ($user !== null) {
            AuthContext::set($user);
        }

        return $next($req);
    }
}
