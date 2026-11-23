<?php
require __DIR__ . '/sesion.php';
require __DIR__ . '/conexion.php';

if (DESARROLLO) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}
// Cuenta caracteres (no bytes). Usa mbstring si está activada y, si no, una alternativa.
function longitud(string $texto): int
{
    if (function_exists('mb_strlen')) {
        return mb_strlen($texto, 'UTF-8');
    }
    return (int) preg_match_all('/./us', $texto);
}

// Escapa texto para mostrarlo de forma segura en HTML.
function e(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}

$resultado = $mysqli->query('SELECT id, nombre FROM categorias ORDER BY nombre');
$categorias = $resultado->fetch_all(MYSQLI_ASSOC);
$resultado->free();
$idsValidos = array_map('intval', array_column($categorias, 'id'));

// Valores iniciales vacíos.
$titulo = '';
$fecha = '';
$categoriaId = 0;
$hora = '';
$descripcion = '';
$errores = [];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  
    $titulo = trim($_POST['titulo'] ?? '');
    $fecha = trim($_POST['fecha'] ?? '');
    $categoriaId = is_scalar($_POST['categoria_id'] ?? null)
        ? (int) $_POST['categoria_id']
        : 0;
    $hora = trim($_POST['hora'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');

    // Validar título.
    if ($titulo === '') {
        $errores['titulo'] = 'El título es obligatorio.';
    } elseif (longitud($titulo) > 120) {
        $errores['titulo'] = 'El título no puede tener más de 120 caracteres.';
    }

    // Validar fecha.
    if ($fecha === '') {
        $errores['fecha'] = 'La fecha es obligatoria.';
    } else {
        $fechaValida = DateTime::createFromFormat('Y-m-d', $fecha);

        if (
            !$fechaValida ||
            $fechaValida->format('Y-m-d') !== $fecha
        ) {
            $errores['fecha'] = 'La fecha no es válida.';
        }
    }

    if (!in_array($categoriaId, $idsValidos, true)) {
        $errores['categoria_id'] = 'Elige una categoría válida.';
    }

    // Validar hora (opcional).
    if ($hora !== '') {
        $horaValida = DateTime::createFromFormat('H:i', $hora);

        if (!$horaValida || $horaValida->format('H:i') !== $hora) {
            $errores['hora'] = 'La hora no es válida.';
        }
    }

    // Validar descripción (opcional).
    if (longitud($descripcion) > 500) {
        $errores['descripcion'] = 'La descripción no puede tener más de 500 caracteres.';
    }

    // Paso 8 · Guardar en MySQL
    // Solo guardamos si es POST y no existen errores de validación.
    if (empty($errores)) {
        try {
            $horaBD = $hora === '' ? null : $hora;
            $descripcionBD = $descripcion === '' ? null : $descripcion;

            $stmt = $mysqli->prepare(
                'INSERT INTO eventos (usuario_id, titulo, fecha, hora, categoria_id, descripcion) VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->bind_param('isssis', $usuarioId, $titulo, $fecha, $horaBD, $categoriaId, $descripcionBD);
            $stmt->execute();
            $stmt->close();

            header('Location: index.php?ok=1');
            exit;

        } catch (Throwable $ex) {
            $errores['general'] = DESARROLLO ? 'No se pudo guardar: ' . $ex->getMessage() : 'No se pudo guardar el evento. Intenta de nuevo.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar evento - AgendaWeb</title>
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
                    <span class="etiqueta">NUEVO EVENTO</span>
                    <h1>Crear evento</h1>
                    <p>Completa la información para agregar un nuevo evento a tu agenda.</p>
                </div>

                <?php if (isset($errores['general'])): ?>
                    <div class="alert alert--error">
                        <?= e($errores['general']) ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="" novalidate>
                    <div class="form-group">
                        <label class="form-label" for="titulo">Título</label>

                        <input
                            type="text"
                            name="titulo"
                            id="titulo"
                            class="form-input<?= isset($errores['titulo']) ? ' is-invalid' : '' ?>"
                            required
                            maxlength="120"
                            value="<?= e($titulo) ?>"
                        >

                        <?php if (isset($errores['titulo'])): ?>
                            <p class="form-error"><?= e($errores['titulo']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="fecha">Fecha</label>

                        <input
                            type="date"
                            name="fecha"
                            id="fecha"
                            class="form-input<?= isset($errores['fecha']) ? ' is-invalid' : '' ?>"
                            required
                            value="<?= e($fecha) ?>"
                        >

                        <?php if (isset($errores['fecha'])): ?>
                            <p class="form-error"><?= e($errores['fecha']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="hora">Hora (opcional)</label>

                        <input
                            type="time"
                            name="hora"
                            id="hora"
                            class="form-input<?= isset($errores['hora']) ? ' is-invalid' : '' ?>"
                            value="<?= e($hora) ?>"
                        >

                        <?php if (isset($errores['hora'])): ?>
                            <p class="form-error"><?= e($errores['hora']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="categoria_id">Categoría</label>

                        <select
                            name="categoria_id"
                            id="categoria_id"
                            class="form-select<?= isset($errores['categoria_id']) ? ' is-invalid' : '' ?>"
                            required
                        >
                            <option value="">— Elige una —</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option
                                    value="<?= (int) $cat['id'] ?>"
                                    <?= $categoriaId === (int) $cat['id'] ? 'selected' : '' ?>
                                >
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

                        <textarea
                            name="descripcion"
                            id="descripcion"
                            rows="4"
                            maxlength="500"
                            class="form-input<?= isset($errores['descripcion']) ? ' is-invalid' : '' ?>"
                        ><?= e($descripcion) ?></textarea>

                        <?php if (isset($errores['descripcion'])): ?>
                            <p class="form-error"><?= e($errores['descripcion']) ?></p>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn-primary">
                        Guardar evento
                    </button>
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