<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\AuthContext;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Level;
use App\Models\Progress;
use App\Models\Ranking;

/**
 * ProgressApiController — POST /api/progress.
 *
 * Único endpoint que dispara `Ranking::recompute` (sync, mismo request).
 * `Progress::upsert` no degrada (GREATEST en SQL); el ranking se recalcula
 * desde la fuente de verdad (`progress`) tras cada upsert.
 *
 * Auth: Bearer obligatorio (rutas en grupo `AuthMiddleware`). El user lo
 * lee de AuthContext, no del JWT directamente.
 */
final class ProgressApiController
{
    public function store(Request $req): Response
    {
        $user = AuthContext::user();

        $levelId   = Validator::int($req->input('level_id'),   ['min' => 1,   'field' => 'level_id']);
        $percentage = Validator::int($req->input('percentage'), ['min' => 0,   'max' => 100,        'field' => 'percentage']);
        $points    = Validator::int($req->input('points'),     ['min' => 0,   'max' => 100_000_000, 'field' => 'points']);

        // Verificar que el level existe (FK también lo impondría con un 500
        // genérico; un 404 explícito es mejor UX y debug).
        $level = Level::findById($levelId);
        if ($level === null) {
            return Response::json(
                ['error' => 'not_found', 'message' => "Level {$levelId} no existe."],
                404,
            );
        }

        $progress = Progress::upsert($user->id, $levelId, $percentage, $points);
        $ranking  = Ranking::recompute($user->id);

        return Response::json([
            'progress' => [
                'id'           => $progress->id,
                'user_id'      => $progress->userId,
                'level_id'     => $progress->levelId,
                'percentage'   => $progress->percentage,
                'points'       => $progress->points,
                'best_run_at'  => $progress->bestRunAt,
            ],
            'ranking' => [
                'total_points'   => $ranking->totalPoints,
                'avg_percentage' => $ranking->avgPercentage,
                'levels_played'  => $ranking->levelsPlayed,
                'updated_at'     => $ranking->updatedAt,
            ],
        ]);
    }
}
