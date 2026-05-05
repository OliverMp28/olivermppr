<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Env;

/**
 * Cookie corta y firmada que transporta el `state` y el `code_verifier` del
 * flujo OAuth (PKCE) entre `/auth/login` y `/auth/callback`.
 *
 * Vive aparte de `$_SESSION` (DAINO_SESSID) a propósito: el SID puede rotar
 * entre requests por `session.use_strict_mode=1` + cookies stale tras gaps
 * largos + requests paralelos, y eso destruye un OAuth flow cuyo state vive
 * en sesión. Esta cookie es independiente, expira en 10 min, y se borra al
 * consumirse — el ciclo de vida del flow OAuth queda desacoplado de la
 * sesión del usuario.
 *
 * Formato del valor:
 *
 *   <state>.<verifier>.<hmac_sha256(state.verifier, APP_KEY)>
 *
 * - `state` y `verifier` son ASCII-safe (state: hex 32; verifier: base64url
 *   43 chars). No contienen `.`, así que `explode('.', ...)` es seguro.
 * - HMAC se firma con `APP_KEY` para garantizar integridad. NO encriptamos:
 *   el `code_verifier` no es secreto en sí mismo (solo unguessable por un
 *   atacante externo, lo cual ya está garantizado por sus 32 bytes random),
 *   y un atacante con acceso a la cookie ya está dentro del browser. Lo que
 *   evitamos con HMAC es que un atacante FORJE una cookie con state/verifier
 *   suyos y nos haga validar contra eso.
 *
 * Atributos cookie:
 *   - Path=/auth: solo se envía a /auth/login y /auth/callback. Mínima
 *     superficie de exposición.
 *   - HttpOnly: no accesible desde JS.
 *   - Secure: en prod sí, en local (HTTP) no. El prefijo `__Host-` se añadirá
 *     en Bloque 8 cuando exista HTTPS prod.
 *   - SameSite=Lax: el callback es navegación cross-site GET desde Vout,
 *     que Lax permite. Strict bloquearía esto.
 *   - Max-Age=600 (10 min): el flow OAuth dura segundos en condiciones
 *     normales. Si el user tarda más, asumimos abandono y forzamos restart.
 */
final class PkceCookie
{
    public const COOKIE_NAME = 'daino_oauth_pkce';
    private const TTL_SECONDS = 600;

    public static function set(string $state, string $verifier): void
    {
        $payload = $state . '.' . $verifier;
        $value   = $payload . '.' . self::sign($payload);
        setcookie(self::COOKIE_NAME, $value, self::cookieAttrs(time() + self::TTL_SECONDS));
    }

    /**
     * One-shot: lee la cookie, valida HMAC, la borra inmediatamente. Devuelve
     * el state+verifier solo si la cookie estaba presente, bien formada y
     * firmada correctamente. En cualquier otro caso devuelve null y el
     * caller decide qué hacer (típicamente: redirect transparente al login).
     *
     * @return array{state: string, verifier: string}|null
     */
    public static function consume(): ?array
    {
        $value = $_COOKIE[self::COOKIE_NAME] ?? null;
        // Borrar siempre — incluso si el valor era inválido — para que un
        // intento de replay/forge no quede en el browser.
        self::clear();

        if (!is_string($value) || $value === '') {
            return null;
        }
        $parts = explode('.', $value);
        if (count($parts) !== 3) {
            return null;
        }
        [$state, $verifier, $providedHmac] = $parts;
        if ($state === '' || $verifier === '' || $providedHmac === '') {
            return null;
        }
        $expectedHmac = self::sign($state . '.' . $verifier);
        if (!hash_equals($expectedHmac, $providedHmac)) {
            return null;
        }
        return ['state' => $state, 'verifier' => $verifier];
    }

    public static function clear(): void
    {
        setcookie(self::COOKIE_NAME, '', self::cookieAttrs(time() - 3600));
    }

    private static function sign(string $payload): string
    {
        $key = Env::require('APP_KEY');
        return hash_hmac('sha256', $payload, $key);
    }

    /**
     * @return array{expires: int, path: string, secure: bool, httponly: bool, samesite: string}
     */
    private static function cookieAttrs(int $expires): array
    {
        $secure = Env::string('APP_ENV', 'production') !== 'local';
        return [
            'expires'  => $expires,
            'path'     => '/auth',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ];
    }
}
