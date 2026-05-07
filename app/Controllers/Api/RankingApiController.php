<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Ranking;

/**
 * RankingApiController — GET /api/ranking (público, sin Bearer).
 *
 * Lista paginada del leaderboard global. JOIN manual a `users` con un único
 * SELECT IN para evitar N+1 (no usamos ORM, así que el N+1 solo aparece si
 * lo escribimos a mano — y aquí lo evitamos explícitamente).
 */
final class RankingApiController
{
    public function index(Request $req): Response
    {
        $limit  = Validator::int($req->query('limit', 10),  ['min' => 1, 'max' => 50, 'required' => false, 'field' => 'limit']);
        $offset = Validator::int($req->query('offset', 0),  ['min' => 0, 'max' => 100_000, 'required' => false, 'field' => 'offset']);

        $rankings = Ranking::topN($limit, $offset);
        $total = Ranking::countAll();

        // Hidratar info de user (username + avatar_url) en una sola query.
        $userIds = array_map(static fn ($r) => $r->userId, $rankings);
        $usersById = self::fetchUsersByIds($userIds);

        $items = [];
        foreach ($rankings as $i => $r) {
            $u = $usersById[$r->userId] ?? null;
            $items[] = [
                'rank'           => $offset + $i + 1,
                'user'           => $u === null ? null : [
                    'vout_id'    => $u['vout_id'],
                    'username'   => $u['username'],
                    'avatar_url' => $u['avatar_url'],
                ],
                'total_points'   => $r->totalPoints,
                'avg_percentage' => $r->avgPercentage,
                'levels_played'  => $r->levelsPlayed,
                'updated_at'     => $r->updatedAt,
            ];
        }

        return Response::json([
            'items'  => $items,
            'total'  => $total,
            'limit'  => $limit,
            'offset' => $offset,
        ]);
    }

    /**
     * @param list<int> $userIds
     * @return array<int, array{vout_id: string, username: string, avatar_url: ?string}>
     */
    private static function fetchUsersByIds(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $stmt = Database::getInstance()->prepare(
            "SELECT id, vout_id, username, avatar_url FROM users WHERE id IN ({$placeholders})",
        );
        $stmt->execute(array_values($userIds));
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[(int) $row['id']] = [
                'vout_id'    => (string) $row['vout_id'],
                'username'   => (string) $row['username'],
                'avatar_url' => $row['avatar_url'] === null ? null : (string) $row['avatar_url'],
            ];
        }
        return $out;
    }
}
