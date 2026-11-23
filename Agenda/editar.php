<?php
require __DIR__ . '/sesion.php';
require __DIR__ . '/conexion.php';
 
if (DESARROLLO) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}
 
function longitud(string $texto): int
{
    if (function_exists('mb_strlen')) {
        return mb_strlen($texto, 'UTF-8');
    }
    return (int) preg_match_all('/./us', $texto);
}
 
function e(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}
 
// Id del evento (viene por GET al abrir y por POST al guardar)
$id = isset($_POST['id']) ? (int) $_POST['id'] : (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: index.php');
    exit;
}
 
$resultado = $mysqli->query('SELECT id, nombre FROM categorias ORDER BY nombre');
$categorias = $resultado->fetch_all(MYSQLI_ASSOC);
$resultado->free();
$idsValidos = array_map('intval', array_column($categorias, 'id'));
 
// Cargar el evento actual
$stmt = $mysqli->prepare(
    'SELECT titulo, fecha, hora, categoria_id, descripcion FROM eventos WHERE id = ? AND usuario_id = ?'
);
$stmt->bind_param('ii', $id, $usuarioId);
$stmt->execute();
$evento = $stmt->get_result()->fetch_assoc();
$stmt->close();
 
if (!$evento) {
    http_response_code(404);
    die('Evento no encontrado.');
}
 
$titulo      = $evento['titulo'];
$fecha       = $evento['fecha'];
$hora        = $evento['hora'] ? substr($evento['hora'], 0, 5) : '';
$categoriaId = (int) $evento['categoria_id'];
$descripcion = $evento['descripcion'] ?? '';
$errores     = [];
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 
    $titulo      = trim($_POST['titulo'] ?? '');
    $fecha       = trim($_POST['fecha'] ?? '');
    $categoriaId = is_scalar($_POST['categoria_id'] ?? null) ? (int) $_POST['categoria_id'] : 0;
    $hora        = trim($_POST['hora'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
 
    if ($titulo === '') {
        $errores['titulo'] = 'El título es obligatorio.';
    } elseif (longitud($titulo) > 120) {
        $errores['titulo'] = 'El título no puede tener más de 120 caracteres.';
    }
 
    if ($fecha === '') {
        $errores['fecha'] = 'La fecha es obligatoria.';
    } else {
        $fechaValida = DateTime::createFromFormat('Y-m-d', $fecha);
        if (!$fechaValida || $fechaValida->format('Y-m-d') !== $fecha) {
            $errores['fecha'] = 'La fecha no es válida.';
        }
    }
 
    if (!in_array($categoriaId, $idsValidos, true)) {
        $errores['categoria_id'] = 'Elige una categoría válida.';
    }
 
    if ($hora !== '') {
        $horaValida = DateTime::createFromFormat('H:i', $hora);
        if (!$horaValida || $horaValida->format('H:i') !== $hora) {
            $errores['hora'] = 'La hora no es válida.';
        }
    }
 
    if (longitud($descripcion) > 500) {
        $errores['descripcion'] = 'La descripción no puede tener más de 500 caracteres.';
    }
 
    if (empty($errores)) {
        try {
            $horaBD        = $hora === '' ? null : $hora;
            $descripcionBD = $descripcion === '' ? null : $descripcion;
 
            $stmt = $mysqli->prepare(
                'UPDATE eventos
                    SET titulo = ?, fecha = ?, hora = ?, categoria_id = ?, descripcion = ?
                  WHERE id = ? AND usuario_id = ?'
            );
            $stmt->bind_param('sssisii', $titulo, $fecha, $horaBD, $categoriaId, $descripcionBD, $id, $usuarioId);
            $stmt->execute();
            $stmt->close();
 
            header('Location: index.php?ok=1');
            exit;
        } catch (Throwable $ex) {
            $errores['general'] = DESARROLLO
                ? 'No se pudo guardar: ' . $ex->getMessage()
                : 'No se pudo guardar el evento. Intenta de nuevo.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar evento - AgendaWeb</title>
    <script src="tema.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap"
        rel="stylesheet"
    >
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <header class="header">
        <div class="contenedor navbar">
            <a href="index.php" class="logo">AgendaWeb</a>
 
            <nav class="nav">
                <a href="index.php">Inicio</a>
                <a href="index.php#agenda">Agenda</a>
                <a href="registrar.php">Nuevo evento</a>
                <a href="index.php#calendario">Calendario</a>
                <a href="index.php#contacto">Contacto</a>
            </nav>
 
            <button class="tema-toggle" id="alternarTema" type="button" aria-label="Activar modo oscuro">
                <span aria-hidden="true">🌙</span>
                <span class="tema-texto">Oscuro</span>
            </button>
 
            <a href="index.php" class="btn btn-pequeno">← Volver</a>
        </div>
    </header>
 
    <main class="pagina-registrar">
        <div class="contenedor">
            <div class="registrar-contenido">
 
                <div class="registrar-encabezado">
                    <span class="etiqueta">EDITAR EVENTO</span>
                    <h1>Editar evento</h1>
                    <p>Cambia la información y guarda los cambios.</p>
                </div>
 
                <?php if (isset($errores['general'])): ?>
                    <div class="alert alert--error"><?= e($errores['general']) ?></div>
                <?php endif; ?>
 
                <form method="post" action="" novalidate>
                    <input type="hidden" name="id" value="<?= (int) $id ?>">
 
                    <div class="form-group">
                        <label class="form-label" for="titulo">Título</label>
                        <input type="text" name="titulo" id="titulo"
                               class="form-input<?= isset($errores['titulo']) ? ' is-invalid' : '' ?>"
                               required maxlength="120" value="<?= e($titulo) ?>">
                        <?php if (isset($errores['titulo'])): ?>
                            <p class="form-error"><?= e($errores['titulo']) ?></p>
                        <?php endif; ?>
                    </div>
 
                    <div class="form-group">
                        <label class="form-label" for="fecha">Fecha</label>
                        <input type="date" name="fecha" id="fecha"
                               class="form-input<?= isset($errores['fecha']) ? ' is-invalid' : '' ?>"
                               required value="<?= e($fecha) ?>">
                        <?php if (isset($errores['fecha'])): ?>
                            <p class="form-error"><?= e($errores['fecha']) ?></p>
                        <?php endif; ?>
                    </div>
 
                    <div class="form-group">
                        <label class="form-label" for="hora">Hora (opcional)</label>
                        <input type="time" name="hora" id="hora"
                               class="form-input<?= isset($errores['hora']) ? ' is-invalid' : '' ?>"
                               value="<?= e($hora) ?>">
                        <?php if (isset($errores['hora'])): ?>
                            <p class="form-error"><?= e($errores['hora']) ?></p>
                        <?php endif; ?>
                    </div>
 
                    <div class="form-group">
                        <label class="form-label" for="categoria_id">Categoría</label>
                        <select name="categoria_id" id="categoria_id"
                                class="form-select<?= isset($errores['categoria_id']) ? ' is-invalid' : '' ?>"
                                required>
                            <option value="">— Elige una —</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= (int) $cat['id'] ?>"
                                    <?= $categoriaId === (int) $cat['id'] ? 'selected' : '' ?>>
                                    <?= e($cat['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errores['categoria_id'])): ?>
                            <p class="form-error"><?= e($errores['categoria_id']) ?></p>
                        <?php endif; ?>
                    </div>
 
                    <div class="form-group">
                        <label class="form-label" for="descripcion">Descripción (opcional)</label>
                        <textarea name="descripcion" id="descripcion" rows="4" maxlength="500"
                                  class="form-input<?= isset($errores['descripcion']) ? ' is-invalid' : '' ?>"
                        ><?= e($descripcion) ?></textarea>
                        <?php if (isset($errores['descripcion'])): ?>
                            <p class="form-error"><?= e($errores['descripcion']) ?></p>
                        <?php endif; ?>
                    </div>
 
                    <button type="submit" class="btn-primary">Guardar cambios</button>
                </form>
 
            </div>
        </div>
    </main>
 
    <footer class="footer">
        <div class="contenedor">
            <p>© 2026 AgendaWeb · Organiza tu tiempo.</p>
        </div>
    </footer>
</body>
</html>