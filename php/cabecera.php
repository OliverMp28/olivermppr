<?php
// Verificar si hay sesión activa (sin iniciar sesión aquí)
$usuario_logueado = !empty($_SESSION["id_usuario"]);
?>
<div>
            <nav class="ventanas">
                <input type="checkbox" id="check">
                <label for="check" class="checkbtn">
                <i class="fas fa-bars"></i> 
                </label>
                    <ul class="opciones-ventanas">
                        <li>
                            <a href="<?php echo BASE_URL; ?>/" class="enlaces-ventanas" >Inicio</a>
                        </li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>/ranking" class="enlaces-ventanas">Ranking</a>
                        </li>
                       <li>
                            <a href="<?php echo BASE_URL; ?>/info" class="enlaces-ventanas">Info</a>
                        </li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>/perfil" class="enlaces-ventanas">Perfil</a>
                        </li>
                        <?php if ($usuario_logueado): ?>
                        <li>
                            <a href="<?php echo BASE_URL; ?>/logout" class="enlaces-ventanas">Cerrar Sesion</a>
                        </li>
                        <?php endif; ?>

                        <!--<li>
                            <a href="https://forms.gle/D8NNqERVakWrsNkA9" class="enlaces-ventanas" target="_blank">Comentarios</a>
                        </li>-->
                    </ul>
            </nav> 

            <h1>Daino</h1>
            
        </div>