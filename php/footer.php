<?php
// El footer ya no gestiona el inicio de sesión.
// Cada página debe iniciar la sesión si la necesita.
?>
<footer id="pielogo"> 
    <div>
        <section class="seccionpie">
          <h1>Navegación</h1>
          <p><a href="<?php echo isset($_SESSION['id_usuario']) ? 'index.php' : 'index_publico.php'; ?>">Inicio</a></p>
          <p><a href="info.php">Comentarios y Novedades</a></p>
        </section>
  
        <section class="seccionpie">
          <h1>Versión</h1>
          <p>4.0</p>
        </section>
  
        <section class="seccionpie">
          <address>Granada, España</address>
          <small>&copy; Derechos Reservados <?php echo date("Y"); ?></small>
        </section>
        <!-- <div class="recuperar"></div> -->
    </div>
</footer>
