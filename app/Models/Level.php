<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use RuntimeException;

/**
 * Level — nivel generado por un usuario a partir de un MP3.
 *
 * Local-First por defecto: `filePath` es null y `isPublic = false`. Solo cuando
 * el usuario explícitamente publica (`setPublic(true, $path)`) se sube el MP3
 * y se persiste su ruta. El CHECK del schema garantiza la coherencia.
 */
final class Level
{
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public string $title,
        public ?string $artist,
        public int $durationSec,
        public ?int $bpm,
        public int $difficulty,
        public ?string $filePath,
        public readonly string $generatorSeed,
        public bool $isPublic,
        public readonly string $createdAt,
    ) {
    }

    public static function findById(int $id): ?self
    {
        $stmt = Database::getInstance()->prepare('SELECT * FROM levels WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : self::fromRow($row);
    }

    /**
     * @return list<self>
     */
    public static function findByUser(int $userId, int $limit = 50, int $offset = 0): array
    {
        $stmt = Database::getInstance()->prepare(
            'SELECT * FROM levels WHERE user_id = :user_id ORDER BY created_at DESC LIMIT :lim OFFSET :off',
        );
        $stmt->bindValue(':user_id', $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    /**
     * @return list<self>
     */
    public static function findPublic(int $limit = 50, int $offset = 0): array
    {
        $stmt = Database::getInstance()->prepare(
            'SELECT * FROM levels WHERE is_public = TRUE ORDER BY created_at DESC LIMIT :lim OFFSET :off',
        );
        $stmt->bindValue(':lim', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    public static function countByUser(int $userId): int
    {
        $stmt = Database::getInstance()->prepare('SELECT COUNT(*) AS c FROM levels WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch();
        return $row === false ? 0 : (int) $row['c'];
    }

    public static function countPublic(): int
    {
        $stmt = Database::getInstance()->query('SELECT COUNT(*) AS c FROM levels WHERE is_public = TRUE');
        $row = $stmt->fetch();
        return $row === false ? 0 : (int) $row['c'];
    }

    /**
     * Existe ya un nivel público con este (user_id, title)? Anti-spam para
     * cuando un user intenta publicar dos veces el mismo título. Case-insensitive
     * gracias al collation `utf8mb4_unicode_ci` del schema.
     */
    public static function publicTitleExistsForUser(int $userId, string $title, ?int $excludeId = null): bool
    {
        $sql = 'SELECT 1 FROM levels WHERE user_id = :user_id AND title = :title AND is_public = TRUE';
        $params = ['user_id' => $userId, 'title' => $title];
        if ($excludeId !== null) {
            $sql .= ' AND id <> :exclude';
            $params['exclude'] = $excludeId;
        }
        $sql .= ' LIMIT 1';
        $stmt = Database::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() !== false;
    }

    /**
     * @param array{
     *     user_id: int,
     *     title: string,
     *     artist?: ?string,
     *     duration_sec: int,
     *     bpm?: ?int,
     *     difficulty: int,
     *     generator_seed: string,
     *     file_path?: ?string,
     *     is_public?: bool
     * } $data
     */
    public static function create(array $data): self
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare(
            'INSERT INTO levels (user_id, title, artist, duration_sec, bpm, difficulty, file_path, generator_seed, is_public)
             VALUES (:user_id, :title, :artist, :duration_sec, :bpm, :difficulty, :file_path, :generator_seed, :is_public)',
        );
        $stmt->execute([
            'user_id'        => $data['user_id'],
            'title'          => $data['title'],
            'artist'         => $data['artist'] ?? null,
            'duration_sec'   => $data['duration_sec'],
            'bpm'            => $data['bpm'] ?? null,
            'difficulty'     => $data['difficulty'],
            'file_path'      => $data['file_path'] ?? null,
            'generator_seed' => $data['generator_seed'],
            'is_public'      => ($data['is_public'] ?? false) ? 1 : 0,
        ]);
        $id = (int) $pdo->lastInsertId();
        $created = self::findById($id);
        if ($created === null) {
            throw new RuntimeException("Level::create no pudo recuperar la fila recién creada (id={$id}).");
        }
        return $created;
    }

    /**
     * Cambio de visibilidad. Si se publica, exige path; el CHECK del schema lo
     * fuerza también a nivel BD para defensa en profundidad.
     */
    public function setPublic(bool $public, ?string $filePath = null): void
    {
        if ($public && $filePath === null && $this->filePath === null) {
            throw new RuntimeException('No se puede publicar un nivel sin file_path.');
        }
        $stmt = Database::getInstance()->prepare(
            'UPDATE levels SET is_public = :is_public, file_path = COALESCE(:file_path, file_path) WHERE id = :id',
        );
        $stmt->execute([
            'is_public' => $public ? 1 : 0,
            'file_path' => $filePath,
            'id'        => $this->id,
        ]);
        $this->isPublic = $public;
        if ($filePath !== null) {
            $this->filePath = $filePath;
        }
    }

    public function delete(): void
    {
        $stmt = Database::getInstance()->prepare('DELETE FROM levels WHERE id = :id');
        $stmt->execute(['id' => $this->id]);
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function fromRow(array $row): self
    {
        return new self(
            id:             (int) $row['id'],
            userId:         (int) $row['user_id'],
            title:          (string) $row['title'],
            artist:         $row['artist'] === null ? null : (string) $row['artist'],
            durationSec:    (int) $row['duration_sec'],
            bpm:            $row['bpm'] === null ? null : (int) $row['bpm'],
            difficulty:     (int) $row['difficulty'],
            filePath:       $row['file_path'] === null ? null : (string) $row['file_path'],
            generatorSeed:  (string) $row['generator_seed'],
            isPublic:       (bool) $row['is_public'],
            createdAt:      (string) $row['created_at'],
        );
    }
}
