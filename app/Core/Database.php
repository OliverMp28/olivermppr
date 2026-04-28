<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Singleton PDO contra MariaDB.
 *
 * Una conexión por request, configuración fija (excepciones, fetch assoc,
 * prepared statements reales, charset utf8mb4). Sin persistencia: PHP-FPM
 * con OPcache+JIT no la necesita y complicaría las transacciones.
 *
 * Los Models consumen `Database::getInstance()->prepare(...)` directo —
 * no hay ORM ni active record en este mini-framework.
 */
final class Database
{
    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $host = Env::string('DB_HOST');
        $port = Env::int('DB_PORT', 3306);
        $database = Env::string('DB_DATABASE');
        $user = Env::string('DB_USER');
        $password = Env::string('DB_PASSWORD', '');

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $host,
            $port,
            $database,
        );

        self::$instance = new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_PERSISTENT         => false,
        ]);

        return self::$instance;
    }

    /**
     * Reset de la instancia. Solo para tests; en runtime nunca debería usarse.
     */
    public static function reset(): void
    {
        self::$instance = null;
    }
}
