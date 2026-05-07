<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\AuthContext;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\User;
use App\Services\JwtVerifier;
use Closure;

/**
 * AuthMiddleware — gating de rutas autenticadas.
 *
 *  - Rutas API (path empieza por `/api/`): exige `Authorization: Bearer <jwt>`
 *    válido. Falla con 401 JSON si falta o no verifica.
 *  - Rutas web: exige sesión iniciada (`vout_id` en `$_SESSION`). Si falta,
 *    redirige a `/auth/login`.
 *
 * NO almacena claims en ningún sitio compartido — los controllers leen lo que
 * necesiten desde `$_SESSION` (web) o re-validan el JWT (API). Si en el futuro
 * Bloque 7 necesita claims propagados, se añade un AuthContext minimal aquí.
 *
 * El JwtVerifier es inyectable para tests; por defecto se construye con
 * `JwtVerifier::fromEnv()` la primera vez que se necesita.
 */
final class AuthMiddleware
{
    public function __construct(
        private readonly ?JwtVerifier $verifier = null,
    ) {
    }

    public function __invoke(Request $req, Closure $next): Response
    {
        $isApi = str_starts_with($req->path(), '/api/');

        if ($isApi) {
            $token = $req->bearerToken();
            if ($token === null) {
                return Response::json(
                    ['error' => 'unauthorized', 'message' => 'Missing Bearer token.'],
                    401,
                );
            }
            try {
                $claims = ($this->verifier ?? JwtVerifier::fromEnv())->verify($token);
            } catch (\Throwable $e) {
                return Response::json(
                    ['error' => 'invalid_token', 'message' => $e->getMessage()],
                    401,
                );
            }

            // Resolver el User local desde el claim `vout_id` del JWT.
            // Vout incluye este UUID directamente en el token (ver
            // integration-guide §"Datos dentro del Token"), así que el
            // lookup es directo contra la columna indexada `users.vout_id`
            // sin round-trip a `/api/v1/user/me`. La guía recomienda
            // explícitamente este patrón sobre el `sub` (que puede cambiar
            // de formato en el futuro).
            $voutId = $claims['vout_id'] ?? null;
            if (!is_string($voutId) || $voutId === '') {
                return Response::json(
                    ['error' => 'invalid_token', 'message' => 'JWT sin claim `vout_id`.'],
                    401,
                );
            }
            $user = User::findByVoutId($voutId);
            if ($user === null) {
                // El JWT verifica firma pero no hay row en nuestra BD.
                // Caminos posibles:
                //  - El user fue borrado tras la emisión del JWT.
                //  - El user nunca pasó por /auth/callback en este Daino
                //    (improbable: el JWT se obtiene exactamente ahí).
                // Frontend debe forzar re-login.
                return Response::json(
                    ['error' => 'user_missing', 'message' => 'Authenticated user no longer exists.'],
                    401,
                );
            }
            AuthContext::set($user);

            return $next($req);
        }

        // Web mode
        if (!Session::has('vout_id')) {
            return Response::redirect('/auth/login');
        }
        return $next($req);
    }
}
