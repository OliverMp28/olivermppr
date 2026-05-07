<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * PayloadTooLargeException — el body del request excede el límite admitido.
 *
 * Mapeada por `public/index.php` (vía bootstrap exception handler) a 413
 * Payload Too Large. Distinta de ValidationException (422) porque el código
 * HTTP estándar para "tu archivo es demasiado grande" es 413, no 422.
 */
final class PayloadTooLargeException extends RuntimeException
{
    public function __construct(string $message = 'Payload exceeds size limit.')
    {
        parent::__construct($message);
    }
}
