<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\AuthContext;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Comment;
use App\Models\Level;

/**
 * CommentApiController — comentarios per-level con moderación blanda.
 *
 *   - POST /api/comments  (auth) {level_id, body}
 *   - GET  /api/comments  (público) ?level_id={id}&limit&offset
 *
 * Diseño:
 *   - JOIN manual a `users` con un único SELECT IN (evita N+1).
 *   - `is_visible=false` se respeta: anónimos solo ven visibles. La
 *     moderación admin (toggle visibility) queda diferida — no hay rol
 *     admin en `users` todavía.
 */
final class CommentApiController
{
    public function store(Request $req): Response
    {
        $user = AuthContext::user();

        $levelId = Validator::int($req->input('level_id'), ['min' => 1, 'field' => 'level_id']);
        $body    = Validator::string($req->input('body'),  ['min' => 1, 'max' => 400, 'field' => 'body']);

        if (Level::findById($levelId) === null) {
            return Response::json(['error' => 'not_found', 'message' => "Level {$levelId} no existe."], 404);
        }

        $comment = Comment::create([
            'user_id'  => $user->id,
            'level_id' => $levelId,
            'body'     => $body,
        ]);

        return Response::json([
            'id'         => $comment->id,
            'user_id'    => $comment->userId,
            'level_id'   => $comment->levelId,
            'body'       => $comment->body,
            'is_visible' => $comment->isVisible,
            'created_at' => $comment->createdAt,
            'user'       => [
                'vout_id'    => $user->voutId,
                'username'   => $user->username,
                'avatar_url' => $user->avatarUrl,
            ],
        ], 201);
    }

    public function index(Request $req): Response
    {
        $levelId = Validator::int($req->query('level_id'), ['min' => 1, 'field' => 'level_id']);
        $limit   = Validator::int($req->query('limit', 20),  ['min' => 1, 'max' => 50, 'required' => false, 'field' => 'limit']);
        $offset  = Validator::int($req->query('offset', 0),  ['min' => 0, 'max' => 100_000, 'required' => false, 'field' => 'offset']);

        if (Level::findById($levelId) === null) {
            return Response::json(['error' => 'not_found', 'message' => "Level {$levelId} no existe."], 404);
        }

        $comments = Comment::findByLevel($levelId, true, $limit, $offset);
        $total = Comment::countByLevel($levelId, true);

        $userIds = array_unique(array_map(static fn ($c) => $c->userId, $comments));
        $usersById = self::fetchUsersByIds($userIds);

        $items = [];
        foreach ($comments as $c) {
            $u = $usersById[$c->userId] ?? null;
            $items[] = [
                'id'         => $c->id,
                'user_id'    => $c->userId,
                'level_id'   => $c->levelId,
                'body'       => $c->body,
                'is_visible' => $c->isVisible,
                'created_at' => $c->createdAt,
                'user'       => $u === null ? null : [
                    'vout_id'    => $u['vout_id'],
                    'username'   => $u['username'],
                    'avatar_url' => $u['avatar_url'],
                ],
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
