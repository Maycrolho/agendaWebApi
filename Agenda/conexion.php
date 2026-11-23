<?php
/* Conexión local (NO se sube a GitHub: está en .gitignore).
   En DomCloud: pon DESARROLLO en false y usa los datos de tu panel. */

// true = muestra errores detallados (solo en tu computadora)
define('DESARROLLO', true);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    //                  servidor     usuario  contraseña  base
    $mysqli = new mysqli('localhost', 'root', '123456', 'agenda');
    $mysqli->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    error_log('Conexión BD: ' . $e->getMessage());
    die(DESARROLLO
        ? 'No se pudo conectar a la base de datos: ' . $e->getMessage()
        : 'No se pudo conectar a la base de datos.');
}