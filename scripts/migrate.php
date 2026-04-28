<?php
declare(strict_types=1);

/**
 * Migration runner mínimo de Daino.
 *
 *   php scripts/migrate.php run     — aplica todas las pendientes en orden
 *                                     alfabético; idempotente (registra cada
 *                                     éxito en la tabla `_migrations`).
 *   php scripts/migrate.php status  — lista cada archivo y si está aplicado.
 *
 * Sin rollback explícito: en dev se dropea + re-runea, en prod se escribe una
 * migración nueva. MariaDB hace implicit commit en DDL — la transacción aquí
 * solo cubre el INSERT en `_migrations`, no la creación de la tabla.
 *
 * Pensado para correr DENTRO del contenedor PHP:
 *     docker compose exec php composer migrate
 *     docker compose exec php composer migrate -- status
 */

require __DIR__ . '/../app/bootstrap.php';

use App\Core\Database;

const MIGRATIONS_DIR = __DIR__ . '/../database/migrations';

$action = $argv[1] ?? 'run';
$pdo = Database::getInstance();

// Bootstrap de la tabla de control.
$pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS _migrations (
    name        VARCHAR(255) NOT NULL,
    applied_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

$applied = array_column(
    $pdo->query('SELECT name FROM _migrations')->fetchAll(),
    'name',
);

$files = array_values(array_filter(
    scandir(MIGRATIONS_DIR) ?: [],
    static fn(string $f): bool => (bool) preg_match('/^\d+_.+\.sql$/', $f),
));
sort($files);

if ($action === 'status') {
    foreach ($files as $f) {
        $mark = in_array($f, $applied, true) ? '[applied]' : '[pending]';
        echo "  {$mark}  {$f}\n";
    }
    exit(0);
}

if ($action !== 'run') {
    fwrite(STDERR, "Uso: php scripts/migrate.php [run|status]\n");
    exit(2);
}

$pending = array_values(array_diff($files, $applied));
if ($pending === []) {
    echo "Nada que aplicar. Todas al día.\n";
    exit(0);
}

foreach ($pending as $file) {
    $sql = file_get_contents(MIGRATIONS_DIR . '/' . $file);
    if ($sql === false || trim($sql) === '') {
        fwrite(STDERR, "FAIL: no se pudo leer {$file}\n");
        exit(1);
    }
    echo "Aplicando: {$file} ... ";
    try {
        $pdo->exec($sql);
        $stmt = $pdo->prepare('INSERT INTO _migrations (name) VALUES (:name)');
        $stmt->execute(['name' => $file]);
        echo "OK\n";
    } catch (Throwable $e) {
        echo "FAIL\n";
        fwrite(STDERR, "  {$e->getMessage()}\n");
        exit(1);
    }
}

echo "Hecho.\n";
