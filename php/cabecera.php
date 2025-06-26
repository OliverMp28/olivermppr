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
                            <a href="<?php echo $usuario_logueado ? './index.php' : './index_publico.php'; ?>" class="enlaces-ventanas" >Inicio</a>
                        </li>
                        <li>
                            <a href="./ranking.php" class="enlaces-ventanas">Ranking</a>
                        </li>
                       <li>
                            <a href="./info.php" class="enlaces-ventanas">Info</a>
                        </li>
                        <li>
                            <a href="./perfil.php" class="enlaces-ventanas">Perfil</a>
                        </li>
                        <?php if ($usuario_logueado): ?>
                        <li>
                            <a href="../controladores_php/cerrar_login.php" class="enlaces-ventanas">Cerrar Sesion</a>
                        </li>
                        <?php endif; ?>

                        <!--<li>
                            <a href="https://forms.gle/D8NNqERVakWrsNkA9" class="enlaces-ventanas" target="_blank">Comentarios</a>
                        </li>-->
                    </ul>
            </nav> 

            <h1>Daino</h1>
            
        </div>