<?php
require __DIR__ . '/sesion.php';
require __DIR__ . '/conexion.php';

// Solo se acepta el borrado por POST (el botón "Borrar" del formulario)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$id = (int) ($_POST['id'] ?? 0);

if ($id > 0) {
    try {
        $stmt = $mysqli->prepare('DELETE FROM eventos WHERE id = ? AND usuario_id = ?');
        $stmt->bind_param('ii', $id, $usuarioId);
        $stmt->execute();
        $stmt->close();
    } catch (Throwable $ex) {
        http_response_code(500);
        die(DESARROLLO ? 'No se pudo borrar: ' . $ex->getMessage() : 'No se pudo borrar el evento.');
    }
}

header('Location: index.php#agenda');
exit;