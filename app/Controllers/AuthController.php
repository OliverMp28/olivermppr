<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Env;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\User;
use App\Services\JwtVerifier;
use App\Services\VoutAuthService;

/**
 * Auth flow contra Vout (OAuth2 Authorization Code + PKCE).
 *
 * Endpoints:
 *   GET  /auth/login     → genera state+verifier, redirect a Vout authorize.
 *   GET  /auth/callback  → valida state, intercambia code, valida JWT, /me, upsert User, sesión.
 *   POST /auth/logout    → destruye sesión + invalida cookie de refresh. Local-only.
 *   POST /auth/refresh   → usa cookie daino_refresh para renovar tokens.
 *   GET  /api/me/token   → devuelve access_token + csrf_token al frontend.
 *
 * CSRF: las rutas POST se protegen con CsrfMiddleware en el router; este
 * controller asume que el middleware ya validó cuando llega.
 *
 * VoutAuthService y JwtVerifier son inyectables por constructor para tests
 * (sin tocar Vout real). En runtime, el Router instancia con `new` sin args
 * y los services usan `fromEnv()` lazy.
 */
final class AuthController
{
    public function __construct(
        private readonly ?VoutAuthService $vout = null,
        private readonly ?JwtVerifier $jwt = null,
    ) {
    }

    public function showLogin(Request $req): Response
    {
        $vout = $this->voutOrEnv();
        $authz = $vout->buildAuthorizationUrl();

        Session::set('oauth_state', $authz['state']);
        Session::set('oauth_code_verifier', $authz['code_verifier']);

        return Response::redirect($authz['url']);
    }

    public function callback(Request $req): Response
    {
        $code  = $req->query('code');
        $state = $req->query('state');
        $error = $req->query('error');
        $storedState    = Session::pull('oauth_state');
        $storedVerifier = Session::pull('oauth_code_verifier');

        // Caso "Cancelar" desde Vout: viene `?error=access_denied&state=...`
        // sin code. Es un flujo legítimo, no un error nuestro — limpiar
        // sesión OAuth y mandar al usuario al inicio sin alarma.
        if (is_string($error) && $error !== '') {
            return Response::html(
                '<!doctype html><html><body style="font-family:sans-serif;padding:2rem">'
                . '<h2>Login cancelado</h2>'
                . '<p>No autorizaste a Daino acceder a tu cuenta de Vout. '
                . '<a href="/">Volver al inicio</a> o <a href="/auth/login">probar de nuevo</a>.</p>'
                . '</body></html>',
                200,
            );
        }

        if (!is_string($code) || $code === '') {
            return Response::html('Callback inválido: falta code.', 400);
        }
        if (!is_string($state) || $state === '') {
            return Response::html('Callback inválido: falta state.', 400);
        }
        if (!is_string($storedState) || !is_string($storedVerifier)) {
            return Response::html(
                'Sesión OAuth expirada — vuelve a /auth/login.',
                400,
            );
        }
        if (!hash_equals($storedState, $state)) {
            return Response::html('State mismatch — posible CSRF en flow OAuth.', 403);
        }

        $vout = $this->voutOrEnv();
        $verifier = $this->jwtOrEnv();

        try {
            $accessToken = $vout->exchangeCodeForToken($code, $storedVerifier);
            $verifier->verify($accessToken->getToken());
            $info = $vout->fetchUserInfo($accessToken->getToken());
        } catch (\Throwable $e) {
            // Auditoría Bloque 3 — BAJO: detalle solo en debug; loggear siempre.
            error_log('[daino] OAuth callback error: ' . $e->getMessage());
            $debug = Env::bool('APP_DEBUG', false);
            $detail = $debug ? ' — ' . htmlspecialchars($e->getMessage()) : '';
            return Response::html('Login con Vout falló — vuelve a intentarlo' . $detail, 502);
        }

        $user = User::findOrCreateByVoutId($info['vout_id'], [
            'username'   => (string) ($info['username'] ?? $info['name'] ?? 'user'),
            'avatar_url' => isset($info['avatar']) ? (string) $info['avatar'] : null,
        ]);
        $user->update(['last_login_at' => date('Y-m-d H:i:s')]);

        Session::regenerate();
        Session::set('user_id', $user->id);
        Session::set('vout_id', $user->voutId);
        Session::set('access_token', $accessToken->getToken());
        $expiresAt = $accessToken->getExpires() ?? (time() + 3600);
        Session::set('access_token_expires_at', $expiresAt);

        $refresh = $accessToken->getRefreshToken();
        if (is_string($refresh) && $refresh !== '') {
            $this->setRefreshCookie($refresh);
        }

        return Response::redirect('/');
    }

    /**
     * Llamada desde el frontend cuando el access_token expira. Requiere la
     * cookie daino_refresh (HttpOnly, Path=/auth/refresh) y CSRF (header).
     * Devuelve nuevo access_token en JSON; rota la cookie con el nuevo
     * refresh_token de Vout.
     */
    public function refresh(Request $req): Response
    {
        $refresh = $_COOKIE['daino_refresh'] ?? null;
        if (!is_string($refresh) || $refresh === '') {
            return Response::json(['error' => 'no_refresh_cookie'], 401);
        }

        $vout = $this->voutOrEnv();
        $verifier = $this->jwtOrEnv();

        try {
            $newToken = $vout->refresh($refresh);
            $verifier->verify($newToken->getToken());
        } catch (\Throwable $e) {
            $this->clearRefreshCookie();
            Session::destroy();
            return Response::json(
                ['error' => 'refresh_failed', 'message' => $e->getMessage()],
                401,
            );
        }

        $expiresAt = $newToken->getExpires() ?? (time() + 3600);
        Session::set('access_token', $newToken->getToken());
        Session::set('access_token_expires_at', $expiresAt);
        $newRefresh = $newToken->getRefreshToken();
        if (is_string($newRefresh) && $newRefresh !== '') {
            $this->setRefreshCookie($newRefresh);
        }

        return Response::json([
            'access_token' => $newToken->getToken(),
            'expires_at'   => $expiresAt,
        ]);
    }

    public function logout(Request $req): Response
    {
        Session::destroy();
        $this->clearRefreshCookie();
        return Response::redirect('/');
    }

    /**
     * Entrega del access_token al frontend en standalone. Cookie de sesión
     * (DAINO_SESSID) es la auth aquí; el Bearer no aplica todavía. Devuelve
     * también el csrf_token para que el frontend lo use en /auth/refresh y
     * /auth/logout.
     */
    public function meToken(Request $req): Response
    {
        if (!Session::has('vout_id')) {
            return Response::json(['error' => 'unauthenticated'], 401);
        }
        $expiresAt = (int) (Session::get('access_token_expires_at') ?? 0);
        $accessToken = Session::get('access_token');
        if (!is_string($accessToken) || $expiresAt <= time()) {
            return Response::json([
                'error'        => 'token_expired',
                'must_refresh' => true,
                'csrf_token'   => Session::csrfToken(),
            ], 401);
        }

        // Auditoría Bloque 3 — MEDIO: re-validar firma/iss/aud/exp antes de
        // entregar el token al frontend. JWKS está cacheado, coste despreciable.
        // Cubre escenarios de rotación de claves Vout, sesión manipulada o
        // token revocado entre callback y request al frontend.
        try {
            $this->jwtOrEnv()->verify($accessToken);
        } catch (\Throwable) {
            Session::set('access_token', null);
            Session::set('access_token_expires_at', 0);
            return Response::json([
                'error'        => 'token_invalid',
                'must_refresh' => true,
                'csrf_token'   => Session::csrfToken(),
            ], 401);
        }

        return Response::json([
            'access_token' => $accessToken,
            'expires_at'   => $expiresAt,
            'csrf_token'   => Session::csrfToken(),
        ]);
    }

    private function voutOrEnv(): VoutAuthService
    {
        return $this->vout ?? VoutAuthService::fromEnv();
    }

    private function jwtOrEnv(): JwtVerifier
    {
        return $this->jwt ?? JwtVerifier::fromEnv();
    }

    private function setRefreshCookie(string $refreshToken): void
    {
        setcookie('daino_refresh', $refreshToken, self::refreshCookieAttrs(time() + 30 * 24 * 3600));
    }

    /**
     * Borra `daino_refresh` reusando EXACTAMENTE los mismos atributos de
     * seguridad (path, secure, httponly, samesite) que setRefreshCookie.
     * Sin esto, navegadores con cookies "Strict-by-default" pueden rehusar
     * sobreescribir la cookie y dejar el refresh_token vivo hasta su expiry
     * natural (30 días). Auditoría Bloque 3 — ALTO.
     */
    private function clearRefreshCookie(): void
    {
        setcookie('daino_refresh', '', self::refreshCookieAttrs(time() - 3600));
    }

    /**
     * @return array{expires: int, path: string, secure: bool, httponly: bool, samesite: string}
     */
    private static function refreshCookieAttrs(int $expires): array
    {
        $secure = Env::string('APP_ENV', 'production') !== 'local';
        return [
            'expires'  => $expires,
            'path'     => '/auth/refresh',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ];
    }
}
