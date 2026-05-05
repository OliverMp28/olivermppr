<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Env;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;

/**
 * Sirve la única ruta HTML server-rendered del juego: `GET /`.
 *
 * Pública por diseño. Daino se juega sin login obligatorio (decisión Bloque 4):
 * cualquiera puede entrar al menú, soltar un MP3 y jugar. Login (vía Vout) solo
 * desbloquea features secundarias — submit de ranking global, comentar, subir
 * MP3 público — que se cablearán en Bloque 7 con AuthMiddleware en sus rutas
 * API correspondientes.
 */
final class HomeController
{
    public function index(Request $req): Response
    {
        $html = View::render('pages.home', [
            'title'       => 'Daino',
            'voutOrigin'  => Env::string('VOUT_ORIGIN', ''),
            'uploadMaxMb' => Env::int('UPLOAD_MAX_SIZE_MB', 10),
        ]);

        return Response::html($html);
    }
}
