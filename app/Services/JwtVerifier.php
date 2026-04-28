<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Env;
use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\StrictValidAt;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;
use Psr\Clock\ClockInterface;
use RuntimeException;

/**
 * Valida JWTs RS256 emitidos por Vout.
 *
 * Constraints aplicadas (doc 07 §E.2):
 *   - SignedWith RS256 con la clave pública JWKS correspondiente al `kid`.
 *   - IssuedBy = VOUT_ISSUER.
 *   - PermittedFor (aud) = VOUT_CLIENT_ID.
 *   - StrictValidAt con leeway de 60s (clock drift Vout↔Daino).
 *
 * Algoritmo whitelist: SOLO RS256. `none` y simétricos rechazados explícitamente.
 */
final class JwtVerifier
{
    public function __construct(
        private readonly JwksCache $jwks,
        private readonly string $issuer,
        private readonly string $audience,
        private readonly int $leewaySeconds = 60,
    ) {
    }

    public static function fromEnv(?JwksCache $jwks = null): self
    {
        return new self(
            $jwks ?? JwksCache::fromEnv(),
            Env::string('VOUT_ISSUER'),
            Env::string('VOUT_CLIENT_ID'),
        );
    }

    /**
     * Parsea + valida un JWT. Devuelve los claims como array assoc.
     * Lanza RuntimeException si algo falla (firma, claims, formato, etc.).
     *
     * @return array<string, mixed>
     */
    public function verify(string $jwt): array
    {
        $parser = new Token\Parser(new JoseEncoder());

        try {
            $token = $parser->parse($jwt);
        } catch (\Throwable $e) {
            throw new RuntimeException('JWT malformado: ' . $e->getMessage(), 0, $e);
        }

        if (!$token instanceof Token\Plain) {
            throw new RuntimeException('JWT no es un token Plain (¿JWE?). Solo aceptamos JWS RS256.');
        }

        $alg = $token->headers()->get('alg');
        if ($alg !== 'RS256') {
            throw new RuntimeException("Algoritmo no permitido: '{$alg}'. Solo RS256.");
        }

        $kid = $token->headers()->get('kid');
        if (!is_string($kid) || $kid === '') {
            throw new RuntimeException('JWT sin `kid` en header — Vout siempre los emite con kid.');
        }

        $key = $this->jwks->getKey($kid);
        if ($key === null) {
            throw new RuntimeException("Sin clave pública en JWKS para kid='{$kid}'.");
        }

        // forAsymmetricSigner exige una signing-key + verification-key. Como
        // solo verificamos (nunca firmamos), pasamos la misma para ambas.
        $config = Configuration::forAsymmetricSigner(new Sha256(), $key, $key);

        $constraints = [
            new SignedWith($config->signer(), $config->verificationKey()),
            new IssuedBy($this->issuer),
            new PermittedFor($this->audience),
            // StrictValidAt: exige iat + nbf + exp presentes y válidos. Vout
            // emite los tres (ver integration-guide.md "Datos dentro del Token
            // (Claims)": iss, sub, aud, exp, iat, nbf, jti, scopes), así que
            // ejercemos la validación máxima.
            new StrictValidAt(self::utcClock(), new DateInterval("PT{$this->leewaySeconds}S")),
        ];

        try {
            $config->validator()->assert($token, ...$constraints);
        } catch (RequiredConstraintsViolated $e) {
            throw new RuntimeException('JWT inválido: ' . $e->getMessage(), 0, $e);
        }

        return $token->claims()->all();
    }

    /**
     * Reloj UTC PSR-20. lcobucci/clock no está instalado y `psr/clock` solo
     * provee la interfaz; metemos una implementación trivial inline.
     */
    private static function utcClock(): ClockInterface
    {
        return new class implements ClockInterface {
            public function now(): DateTimeImmutable
            {
                return new DateTimeImmutable('now', new DateTimeZone('UTC'));
            }
        };
    }
}
