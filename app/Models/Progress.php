<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use RuntimeException;

/**
 * Progress — mejor partida de cada (user, level).
 *
 * `upsert` es atómico vía INSERT ... ON DUPLICATE KEY UPDATE: si ya existe la
 * combinación (user_id, level_id), actualiza percentage/points solo si la
 * nueva puntuación supera la guardada (no degradamos progreso).
 */
final class Progress
{
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly int $levelId,
        public int $percentage,
        public int $points,
        public string $bestRunAt,
    ) {
    }

    public static function findByUserAndLevel(int $userId, int $levelId): ?self
    {
        $stmt = Database::getInstance()->prepare(
            'SELECT * FROM progress WHERE user_id = :user_id AND level_id = :level_id',
        );
        $stmt->execute(['user_id' => $userId, 'level_id' => $levelId]);
        $row = $stmt->fetch();
        return $row === false ? null : self::fromRow($row);
    }

    /**
     * Inserta o actualiza el progreso. Solo sube percentage/points si superan
     * los previos. Devuelve la fila resultante (recién insertada o actualizada).
     */
    public static function upsert(int $userId, int $levelId, int $percentage, int $points): self
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare(
            'INSERT INTO progress (user_id, level_id, percentage, points)
             VALUES (:user_id, :level_id, :percentage, :points)
             ON DUPLICATE KEY UPDATE
                percentage  = GREATEST(percentage, VALUES(percentage)),
                points      = GREATEST(points, VALUES(points)),
                best_run_at = CASE
                    WHEN VALUES(points) > points THEN CURRENT_TIMESTAMP
                    ELSE best_run_at
                END',
        );
        $stmt->execute([
            'user_id'    => $userId,
            'level_id'   => $levelId,
            'percentage' => $percentage,
            'points'     => $points,
        ]);
        $row = self::findByUserAndLevel($userId, $levelId);
        if ($row === null) {
            throw new RuntimeException(
                "Progress::upsert no pudo recuperar la fila tras upsert (user={$userId}, level={$levelId}).",
            );
        }
        return $row;
    }

    public function delete(): void
    {
        $stmt = Database::getInstance()->prepare('DELETE FROM progress WHERE id = :id');
        $stmt->execute(['id' => $this->id]);
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function fromRow(array $row): self
    {
        return new self(
            id:         (int) $row['id'],
            userId:     (int) $row['user_id'],
            levelId:    (int) $row['level_id'],
            percentage: (int) $row['percentage'],
            points:     (int) $row['points'],
            bestRunAt:  (string) $row['best_run_at'],
        );
    }
}
