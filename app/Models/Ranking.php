<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use RuntimeException;

/**
 * Ranking — agregado denormalizado por usuario para lecturas rápidas del
 * leaderboard global.
 *
 * `recompute(userId)` recalcula desde `progress` y hace upsert. Pensado para
 * llamarse desde el endpoint de Bloque 7 tras cada Progress::upsert. Si el
 * agregado se desincroniza por alguna razón, una llamada vuelve a alinearlo.
 */
final class Ranking
{
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public int $totalPoints,
        public int $avgPercentage,
        public int $levelsPlayed,
        public string $updatedAt,
    ) {
    }

    public static function findByUser(int $userId): ?self
    {
        $stmt = Database::getInstance()->prepare('SELECT * FROM rankings WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch();
        return $row === false ? null : self::fromRow($row);
    }

    /**
     * @return list<self>
     */
    public static function topN(int $n = 10, int $offset = 0): array
    {
        $stmt = Database::getInstance()->prepare(
            'SELECT * FROM rankings ORDER BY total_points DESC, levels_played DESC LIMIT :n OFFSET :off',
        );
        $stmt->bindValue(':n', $n, \PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    public static function countAll(): int
    {
        $stmt = Database::getInstance()->query('SELECT COUNT(*) AS c FROM rankings');
        $row = $stmt->fetch();
        return $row === false ? 0 : (int) $row['c'];
    }

    /**
     * Recalcula el agregado desde `progress` y hace upsert.
     */
    public static function recompute(int $userId): self
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT
                COALESCE(SUM(points), 0)              AS total_points,
                COALESCE(ROUND(AVG(percentage)), 0)   AS avg_percentage,
                COUNT(*)                              AS levels_played
             FROM progress WHERE user_id = :user_id',
        );
        $stmt->execute(['user_id' => $userId]);
        $agg = $stmt->fetch();
        if ($agg === false) {
            throw new RuntimeException("Ranking::recompute fetch falló para user_id={$userId}.");
        }

        $upsert = $pdo->prepare(
            'INSERT INTO rankings (user_id, total_points, avg_percentage, levels_played)
             VALUES (:user_id, :total_points, :avg_percentage, :levels_played)
             ON DUPLICATE KEY UPDATE
                total_points   = VALUES(total_points),
                avg_percentage = VALUES(avg_percentage),
                levels_played  = VALUES(levels_played)',
        );
        $upsert->execute([
            'user_id'        => $userId,
            'total_points'   => (int) $agg['total_points'],
            'avg_percentage' => (int) $agg['avg_percentage'],
            'levels_played'  => (int) $agg['levels_played'],
        ]);

        $row = self::findByUser($userId);
        if ($row === null) {
            throw new RuntimeException("Ranking::recompute no pudo recuperar la fila tras upsert (user_id={$userId}).");
        }
        return $row;
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function fromRow(array $row): self
    {
        return new self(
            id:            (int) $row['id'],
            userId:        (int) $row['user_id'],
            totalPoints:   (int) $row['total_points'],
            avgPercentage: (int) $row['avg_percentage'],
            levelsPlayed:  (int) $row['levels_played'],
            updatedAt:     (string) $row['updated_at'],
        );
    }
}
