<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * ValidationException — entrada inválida en un endpoint.
 *
 * `public/index.php` (vía bootstrap exception handler) la mapea a 422 JSON
 * con shape `{error: 'validation', fields: {<key>: '<msg>'}}`. El frontend
 * pinta el mensaje junto al campo correspondiente.
 */
final class ValidationException extends RuntimeException
{
    /** @param array<string, string> $fields  Mapa de campo → mensaje de error. */
    public function __construct(public readonly array $fields, string $message = 'Validation failed.')
    {
        parent::__construct($message);
    }
}
