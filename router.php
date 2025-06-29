<?php

// Obtener la URL solicitada y el método HTTP
$request_uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// // Definir el directorio base si el proyecto está en una subcarpeta (ej. /dino_HTML)
// $base_path = '/dino_HTML';
// $route = str_replace($base_path, '', $request_uri);
// El directorio base ($base_dir) se calcula dinámicamente en index.php
// y está disponible en este scope. Usarlo hace que el enrutador sea portable.
$route = str_replace($base_dir, '', $request_uri);
$route = strtok($route, '?'); // Eliminar query strings de la ruta (ej. ?id=1)

// Si la ruta está vacía, es la página de inicio
if ($route === '') {
    $route = '/';
}


//rutas de la aplicación
// Formato: 'ruta' => ['METODO' => 'archivo_a_cargar.php']
$routes = [
    // La ruta '/' o '/inicio' se maneja de forma especial más abajo
    '/ranking' => ['GET' => 'php/ranking.php'],
    '/info' => [
        'GET' => 'php/info.php',
        'POST' => 'controladores_php/comentario.php' // Para procesar nuevos comentarios
    ],
    '/perfil' => ['GET' => 'php/perfil.php'],
    '/juego' => ['GET' => 'php/juego.php'],
    '/login' => [
        'GET' => 'php/login.php',
        'POST' => 'controladores_php/procesar_login.php'
    ],
    '/registro' => [
        'GET' => 'php/register.php',
        'POST' => 'controladores_php/procesar_register.php'
    ],
    '/logout' => ['GET' => 'controladores_php/cerrar_login.php'],
    
    // Endpoints de la API para llamadas desde JavaScript
    '/api/progreso' => ['POST' => 'controladores_php/gestionar_progreso.php'],
    '/api/migrar-progreso' => ['POST' => 'controladores_php/migrar_progreso_local.php']
];

//funcion para enrutar la petición
function route_request($route, $method, $routes) {
    $varsEntronoCliente = ' <script>
        var BASE_URL = "' . BASE_URL . '";
    </script>';
    
    

    // Manejo especial para la página de inicio, que depende del estado de la sesión
    if (($route === '/' || $route === '/inicio') && $method === 'GET') {
        $usuario_logueado = !empty($_SESSION["id_usuario"]);
        $file_to_include = $usuario_logueado ? 'php/index.php' : 'php/index_publico.php';
        require_once(ROOT_PATH . '/controladores_php/conectar.php');
        echo $varsEntronoCliente;
        require ROOT_PATH . '/' . $file_to_include;
        return; // Detener el procesamiento aquí
    }

    if (array_key_exists($route, $routes) && array_key_exists($method, $routes[$route])) {
        if($method == 'GET' && str_starts_with($routes[$route]['GET'], 'php/')){
            echo $varsEntronoCliente;
        }

        require_once(ROOT_PATH . '/controladores_php/conectar.php');
        require ROOT_PATH . '/' . $routes[$route][$method];
    } else {
        // Manejar página no encontrada (404)
        http_response_code(404);
        // Puedes crear un archivo 404.php más elaborado
        echo "<h1>404 - Página no encontrada</h1>";
    }
}

// Ejecutar el enrutador
route_request($route, $method, $routes);

?>
