<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use RuntimeException;

/**
 * Comment — comentarios per-level (level_id no nulo) o globales (level_id NULL).
 *
 * `is_visible` mantiene la moderación blanda del legacy: se puede ocultar un
 * comentario sin borrarlo. `body` está acotado a 400 caracteres por schema.
 */
final class Comment
{
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly ?int $levelId,
        public string $body,
        public bool $isVisible,
        public readonly string $createdAt,
    ) {
    }

    public static function findById(int $id): ?self
    {
        $stmt = Database::getInstance()->prepare('SELECT * FROM comments WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : self::fromRow($row);
    }

    /**
     * @return list<self>
     */
    public static function findByLevel(int $levelId, bool $onlyVisible = true, int $limit = 50, int $offset = 0): array
    {
        $sql = 'SELECT * FROM comments WHERE level_id = :level_id';
        if ($onlyVisible) {
            $sql .= ' AND is_visible = TRUE';
        }
        $sql .= ' ORDER BY created_at DESC LIMIT :lim OFFSET :off';
        $stmt = Database::getInstance()->prepare($sql);
        $stmt->bindValue(':level_id', $levelId, \PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    public static function countByLevel(int $levelId, bool $onlyVisible = true): int
    {
        $sql = 'SELECT COUNT(*) AS c FROM comments WHERE level_id = :level_id';
        if ($onlyVisible) {
            $sql .= ' AND is_visible = TRUE';
        }
        $stmt = Database::getInstance()->prepare($sql);
        $stmt->execute(['level_id' => $levelId]);
        $row = $stmt->fetch();
        return $row === false ? 0 : (int) $row['c'];
    }

    /**
     * @param array{user_id: int, level_id?: ?int, body: string} $data
     */
    public static function create(array $data): self
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare(
            'INSERT INTO comments (user_id, level_id, body) VALUES (:user_id, :level_id, :body)',
        );
        $stmt->execute([
            'user_id'  => $data['user_id'],
            'level_id' => $data['level_id'] ?? null,
            'body'     => $data['body'],
        ]);
        $id = (int) $pdo->lastInsertId();
        $created = self::findById($id);
        if ($created === null) {
            throw new RuntimeException("Comment::create no pudo recuperar la fila recién creada (id={$id}).");
        }
        return $created;
    }

    public function setVisibility(bool $visible): void
    {
        $stmt = Database::getInstance()->prepare(
            'UPDATE comments SET is_visible = :is_visible WHERE id = :id',
        );
        $stmt->execute(['is_visible' => $visible ? 1 : 0, 'id' => $this->id]);
        $this->isVisible = $visible;
    }

    public function delete(): void
    {
        $stmt = Database::getInstance()->prepare('DELETE FROM comments WHERE id = :id');
        $stmt->execute(['id' => $this->id]);
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function fromRow(array $row): self
    {
        return new self(
            id:        (int) $row['id'],
            userId:    (int) $row['user_id'],
            levelId:   $row['level_id'] === null ? null : (int) $row['level_id'],
            body:      (string) $row['body'],
            isVisible: (bool) $row['is_visible'],
            createdAt: (string) $row['created_at'],
        );
    }
}
