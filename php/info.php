<?php 
// Incluir funciones de utilidad
require_once(ROOT_PATH . '/php/utils/functions.php');

$usuario_logueado = isset($_SESSION['id_usuario']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/modelo.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/info.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.13.0/css/all.min.css" rel="stylesheet">
    <title>Info - Daino</title>
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/img/foto_dino2.2.png">  
    

</head>
<body>
    <header>
        <?php include(ROOT_PATH . '/php/cabecera.php'); ?>
    </header>



    <main id="cuerpo">
<!-- 
        <p>
            <h1>
                RUTAS
            </h1>
            Base url: <span> <?php echo BASE_URL; ?> </span>
            ROOT_PATH : <span> <?php echo ROOT_PATH; ?> </span>
        </p> -->
        <div id="section-darkmode">
            <div class="contenedor-darkmode">
                <input type="checkbox" id="check-darkmode" >
                <label for="check-darkmode" id="boton-darkmode"></label>
            </div>
        </div>

        <div class="recuperar"></div>

        <div class="info-container">
            <div class="info-header">
                <h1>Novedades y Actualizaciones</h1>
            </div>

            <!-- Contenedor con Pestañas -->
            <div class="tab-container">
                <div class="tab-nav">
                    <button class="tab-link active" data-tab="versions">Historial de Versiones</button>
                    <button class="tab-link" data-tab="comments">Comentarios</button>
                </div>

                <!-- Contenido de las Pestañas -->
                <div id="versions" class="tab-content active">
                    <div class="version-history">
                        <div class="version-card">
                            <div class="version-card-header">
                                <div class="version-title">
                                    <h2>Versión 4</h2>
                                    <span class="badge new-badge">Versión Actual</span>
                                </div>
                                <!-- <span class="version-date"><?php echo format_release_date('2025-07-10'); ?></span> -->
                            </div>
                            <div class="version-card-body">
                                <p class="version-description">Una actualización mayor centrada en el uso sin registro, rutas amigables, seguridad y mejora en la experiencia del usuario.</p>
                                <div class="sub-version-timeline">
                                    <!-- Sub-version 4.1 (la mas reciente primero) -->
                                    <div class="sub-version">
                                        <div class="sub-version-header">
                                            <h4>Versión 4.1</h4>
                                            <div class="sub-version-meta">
                                                <?php display_new_badge_if_recent('2025-06-29'); ?>
                                                <span class="version-date"><?php echo format_release_date('2025-06-29'); ?></span>
                                            </div>
                                        </div>
                                        <ul class="version-changelog">
                                            <li><span class="changelog-tag tag-ui">UI/UX</span> Rediseño completo de la página de info.</li>
                                            <li><span class="changelog-tag tag-feature">Novedad</span> Se ha añadido la opción de migrar el progreso local a la cuenta cuando se inicia sesión.</li>
                                            <li><span class="changelog-tag tag-feature">Novedad</span> Se modificó el sistema de rutas para que sean más amigables.</li>
                                        </ul>                                           
                                    </div>

                                    <!-- Sub-version 4.0 -->
                                    <div class="sub-version">
                                        <div class="sub-version-header">
                                            <h4>Versión 4.0</h4>
                                            <div class="sub-version-meta">
                                                <?php display_new_badge_if_recent('2025-06-20'); ?>
                                                <span class="version-date"><?php echo format_release_date('2025-06-20'); ?></span>
                                            </div>
                                        </div>
                                        <ul class="version-changelog">
                                            <li><span class="changelog-tag tag-feature">Novedad</span> <strong>Uso sin Iniciar Sesión:</strong> Ahora puedes jugar sin registrarte y tu progreso se guarda en el navegador.</li>
                                            <li><span class="changelog-tag tag-security">Seguridad</span> Implementamos parches contra inyección SQL y mejoré la seguridad de las contraseñas.</li>
                                            <li><span class="changelog-tag tag-ui">UI/UX</span> Mejora en la navegación fija de la cabecera.</li>
                                            <li><span class="changelog-tag tag-feature">Novedad</span> Integración de un nuevo sistema de caché para acelerar los tiempos de carga.</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="version-card">
                            <div class="version-card-header">
                                <div class="version-title">
                                    <h2>Versión 3</h2>
                                </div>
                                <?php display_new_badge_if_recent('2024-04-15'); ?>
                                <span class="version-date"><?php echo format_release_date('2024-04-15'); ?></span>
                            </div>
                            <div class="version-card-body">
                                <p class="version-summary">Se han añadido mejoras de rendimiento y diseño, así como correcciones de bugs en la jugabilidad. Las mejoras específicas son:</p>
                                <ul class="version-changelog">
                                    <li><span class="changelog-tag tag-feature">Mejora</span> Se ha optimizado el código para que sea adaptable a distintos dispositivos.</li>
                                    <li><span class="changelog-tag tag-feature">Mejora</span> El juego ahora inicia sin necesidad de recargar la página, para mayor fluidez.</li>
                                    <li><span class="changelog-tag tag-feature">Novedad</span> Se añadió un botón "Play" para controlar el inicio.</li>
                                    <li><span class="changelog-tag tag-ui">UI/UX</span> Se optimizó el diseño de los botones y el contenedor del juego.</li>
                                    <li><span class="changelog-tag tag-feature">Novedad</span> Se habilitó la función "saltar" con dos teclas más: flecha arriba y click izquierdo.</li>
                                    <li><span class="changelog-tag tag-ui">UI/UX</span> Se añadió el modo oscuro.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="comments" class="tab-content">
                    <div class="comments-container">
                        <h2>Comentarios de la Comunidad</h2>

                        <?php if ($usuario_logueado): ?>
                            <form action="<?php echo BASE_URL; ?>/info" method="post" id="formulario" class="comment-form">
                                <textarea name="comentario" id="comentario" placeholder="Escribe tu comentario aquí..." required></textarea>
                                <div class="form-actions">
                                    <div>
                                        <input type="checkbox" name="visible" id="visible" value="1" checked>
                                        <label for="visible">Hacer comentario público</label>
                                    </div>
                                    <button type="submit" id="enviar">Enviar</button>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="login-prompt">
                                <h3>¡Únete a la conversación!</h3>
                                <p>Para dejar un comentario, necesitas una cuenta.</p>
                                <div class="prompt-buttons">
                                    <a href="./registro" class="btn-primary">Crear Cuenta</a>
                                    <a href="./login" class="btn-secondary">Iniciar Sesión</a>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="comment-list">
                            <?php 
                                $stmt = $conexion->prepare("SELECT c.comentario, c.fecha, r.usuario FROM comentarios c JOIN register r ON c.id_usuario = r.id WHERE c.visible = 1 ORDER BY c.fecha DESC");
                                
                                $stmt->execute();
                                $stmt->bind_result($comentario, $fecha, $usuario);
                                while ($stmt->fetch()): 
                            ?>
           
                                <div class="comment">
                                    <div class="comment-meta">
                                        <span class="username"><?php echo htmlspecialchars($usuario); ?></span>
                                        <span class="date"><?php echo date("d/m/Y", strtotime($fecha)); ?></span>
                                    </div>
                                    <p class="comment-body"><?php echo htmlspecialchars($comentario); ?></p>
                                </div>
                            <?php 
                                endwhile; 
                                $stmt->close();
                            ?>
                        </div>
                    </div>
                </div>
                
            </div>
            
        </div>
        
        <div class="recuperar"></div>
    </main>
    
    <?php include 'footer.php'; ?>

    <script src="<?php echo BASE_URL; ?>/js/darkmode.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
        const tabs = document.querySelectorAll('.tab-link');
        const tabContents = document.querySelectorAll('.tab-content');

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                //si la pestaña ya esta activa, no hacer nada
                if (tab.classList.contains('active')) {
                    return;
                }

                const targetId = tab.dataset.tab;
                const targetContent = document.getElementById(targetId);

                //ocultar todas las pestañas y contenidos
                tabs.forEach(t => t.classList.remove('active'));
                tabContents.forEach(content => content.classList.remove('active'));

                //mostrar la pestaña y contenido seleccionados
                tab.classList.add('active');
                targetContent.classList.add('active');
            });
        });
    });


    /*--------------- ACORDEÓN -------------------- */
    const accordionHeaders = document.querySelectorAll('.accordion-header');

    accordionHeaders.forEach(header => {
        header.addEventListener('click', () => {
            const content = header.nextElementSibling;
            const icon = header.querySelector('.accordion-icon');

            // Cerrar otros acordeones abiertos
            accordionHeaders.forEach(otherHeader => {
                if (otherHeader !== header) {
                    otherHeader.nextElementSibling.style.maxHeight = null;
                    otherHeader.nextElementSibling.classList.remove('active');
                    otherHeader.querySelector('.accordion-icon').style.transform = 'rotate(0deg)';
                }
            });

            // Abrir/cerrar el actual
            if (content.style.maxHeight) {
                content.style.maxHeight = null;
                content.classList.remove('active');
                icon.style.transform = 'rotate(0deg)';
            } else {
                content.classList.add('active');
                content.style.maxHeight = content.scrollHeight + "px";
                icon.style.transform = 'rotate(180deg)';
            }
        });
    });

    /*--------------- VALIDACIoN FORMULARIO COMENTARIO -------------------- */
    const commentForm = document.getElementById("formulario");
    if (commentForm) {
        commentForm.addEventListener("submit", function(event) {
            const comentario = document.getElementById("comentario").value;
            if (comentario.trim() === "") {
                alert("Por favor, escribe un comentario.");
                event.preventDefault(); // Evita que el formulario se envíe
            }
        });
    }
    </script>
</body>
</html>