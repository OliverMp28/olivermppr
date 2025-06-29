<?php
/**
 * Funciones de utilidad para el proyecto Daino.
 */

/**
 * Muestra una etiqueta "Nuevo" si la fecha de publicación es reciente.
 * @param string $date_string La fecha en formato Y-m-d.
 * @param int $days_limit El número de días para considerar una versión como "nueva".
 */
function display_new_badge_if_recent($date_string, $days_limit = 7) {
    try {
        $release_date = new DateTime($date_string);
        $now = new DateTime();

        $release_date->setTime(0, 0, 0);
        $now->setTime(0, 0, 0);

        $diff = $now->diff($release_date);
        $days = (int)$diff->format('%r%a'); // %r da el signo (+ o -), %a el total de días

        if ($days >= -$days_limit && $days <= 0) { //considera hoy y hasta 7 días atrás
            echo '<span class="badge recent-badge">Nuevo</span>';
        }
    } catch (Exception $e) {
        //no hacer nada si la fecha es inválida
    }
}

/**
 * Formatea una fecha para mostrarla de forma relativa (ej: "hoy", "ayer", "hace 5 días").
 * Si la fecha es muy antigua, la muestra en formato largo (ej: "25 de junio de 2025").
 * @param string $date_string La fecha en formato Y-m-d.
 * @return string La fecha formateada y legible.
 */
function format_release_date($date_string) {
    //aseguramos que la localización esté en español
    setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES', 'Spanish');
    
    try {
        $release_date = new DateTime($date_string);
        $now = new DateTime();

        //ignoramos la hora para comparar solo los días
        $release_date->setTime(0, 0, 0);
        $now->setTime(0, 0, 0);

        $diff = $now->diff($release_date);
        $days = (int)$diff->format('%r%a'); // %r da el signo (+ o -), %a el total de días

        if ($days == 0) {
            return 'Publicado hoy';
        } elseif ($days == -1) {
            return 'Publicado ayer';
        } elseif ($days > -7 && $days < 0) {
            return 'Publicado hace ' . abs($days) . ' días';
        } else {
            //para fechas lejanas o futuras, usamos un formato completo y amigable
            if (class_exists('IntlDateFormatter')) {
                 $formatter = new IntlDateFormatter(
                    'es_ES',
                    IntlDateFormatter::LONG,
                    IntlDateFormatter::NONE
                );
                return 'Publicado el ' . $formatter->format($release_date);
            } else {
                //fallback si Intl no está disponible: formato manual en español.
                $meses = [
                    'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
                    'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'
                ];
                $dia = $release_date->format('d');
                $mes = $meses[(int)$release_date->format('n') - 1];
                $anio = $release_date->format('Y');
                return 'Publicado el ' . $dia . ' de ' . $mes . ' de ' . $anio;
            }
        }
    } catch (Exception $e) {
        //en caso de error con la fecha, devolvemos un texto genérico.
        return 'Fecha de publicación';
    }
}
?>
