<?php
/* PLANTILLA (esta sí se sube a GitHub)
   1. Copia este archivo y llámalo conexion.php
   2. Cambia los datos por los reales (en DomCloud, los de tu panel)
   3. conexion.php NUNCA se sube a GitHub (ver .gitignore) */

// true = muestra errores detallados (solo en tu computadora). En DomCloud: false
define('DESARROLLO', false);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    //                  servidor     usuario    contraseña    base
    $mysqli = new mysqli('localhost', 'USUARIO', 'CONTRASENA', 'BASE_DE_DATOS');
    $mysqli->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    error_log('Conexión BD: ' . $e->getMessage());
    die(DESARROLLO
        ? 'No se pudo conectar a la base de datos: ' . $e->getMessage()
        : 'No se pudo conectar a la base de datos.');
}