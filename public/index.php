<?php
declare(strict_types=1);

/**
 * Front controller de Daino.
 *
 * Nginx reescribe todas las peticiones HTTP a este archivo. Es el ÚNICO
 * .php accesible públicamente; todo lo demás vive fuera de /public.
 *
 * Pipeline: bootstrap → instanciar Router → cargar rutas → dispatch → send.
 */

const DAINO_ROOT = __DIR__ . '/..';

require DAINO_ROOT . '/app/bootstrap.php';

use App\Core\Request;
use App\Core\Router;

$router = new Router();
require DAINO_ROOT . '/routes/web.php';
require DAINO_ROOT . '/routes/api.php';

$router->dispatch(Request::capture())->send();
