<?php
session_start();
if (isset($_SESSION['uid'])) {          // ya tiene sesión: no necesita registrarse
    header('Location: index.php');
    exit;
}
require __DIR__ . '/conexion.php';

if (DESARROLLO) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

function e(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}

function longitud(string $texto): int
{
    if (function_exists('mb_strlen')) {
        return mb_strlen($texto, 'UTF-8');
    }
    return (int) preg_match_all('/./us', $texto);
}

$nombre = $correo = '';
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $correoOriginal = trim($_POST['correo'] ?? '');
    $correo = function_exists('mb_strtolower')
        ? mb_strtolower($correoOriginal, 'UTF-8')
        : strtolower($correoOriginal);                       // Ana@x.com = ana@x.com
    $clave  = $_POST['clave']  ?? '';                          // la contraseña NO se recorta
    $clave2 = $_POST['clave2'] ?? '';

    if ($nombre === '' || longitud($nombre) > 80) {
        $errores['nombre'] = 'Escribe tu nombre (máximo 80 caracteres).';
    }
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores['correo'] = 'Escribe un correo válido.';
    }
    if (longitud($clave) < 8) {
        $errores['clave'] = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif ($clave !== $clave2) {
        $errores['clave2'] = 'Las contraseñas no coinciden.';
    }

    if (empty($errores)) {
        $hash = password_hash($clave, PASSWORD_DEFAULT);   // se guarda el HASH, nunca la clave
        try {
            $stmt = $mysqli->prepare('INSERT INTO usuarios (nombre, correo, clave_hash) VALUES (?, ?, ?)');
            $stmt->bind_param('sss', $nombre, $correo, $hash);
            $stmt->execute();
            header('Location: login.php?registrado=1');
            exit;
        } catch (mysqli_sql_exception $ex) {
            if ($ex->getCode() === 1062) {               // 1062 = valor repetido en un UNIQUE
                $errores['correo'] = 'Ese correo ya tiene una cuenta.';
            } else {
                error_log('Registro de usuario (' . $ex->getCode() . '): ' . $ex->getMessage());
                $errores['general'] = DESARROLLO
                    ? 'Error al crear la cuenta: ' . $ex->getMessage()
                    : 'No se pudo crear la cuenta. Intenta de nuevo.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear cuenta - AgendaWeb</title>
    <script src="tema.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <header class="header">
        <div class="contenedor navbar">
            <a href="login.php" class="logo">AgendaWeb</a>
            <button class="tema-toggle" id="alternarTema" type="button" aria-label="Activar modo oscuro">
                <span aria-hidden="true">🌙</span>
                <span class="tema-texto">Oscuro</span>
            </button>
        </div>
    </header>


    <main class="pagina-registrar">
        <div class="contenedor">
            <div class="registrar-contenido">

                <div class="registrar-encabezado">
                    <span class="etiqueta">CUENTA</span>
                    <h1>Crear cuenta</h1>
                    <p>Regístrate para tener tu propia agenda.</p>
                </div>

                <?php if (isset($errores['general'])): ?>
                    <div class="alert alert--error"><?= e($errores['general']) ?></div>
                <?php endif; ?>

                <form method="post" action="" novalidate>
                    <div class="form-group">
                        <label class="form-label" for="nombre">Nombre</label>
                        <input type="text" name="nombre" id="nombre" maxlength="80" required
                               class="form-input<?= isset($errores['nombre']) ? ' is-invalid' : '' ?>"
                               value="<?= e($nombre) ?>">
                        <?php if (isset($errores['nombre'])): ?>
                            <p class="form-error"><?= e($errores['nombre']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="correo">Correo</label>
                        <input type="email" name="correo" id="correo" required
                               class="form-input<?= isset($errores['correo']) ? ' is-invalid' : '' ?>"
                               value="<?= e($correo) ?>">
                        <?php if (isset($errores['correo'])): ?>
                            <p class="form-error"><?= e($errores['correo']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="clave">Contraseña</label>
                        <input type="password" name="clave" id="clave" minlength="8"
                               autocomplete="new-password" required
                               class="form-input<?= isset($errores['clave']) ? ' is-invalid' : '' ?>">
                        <?php if (isset($errores['clave'])): ?>
                            <p class="form-error"><?= e($errores['clave']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="clave2">Repítela</label>
                        <input type="password" name="clave2" id="clave2" minlength="8"
                               autocomplete="new-password" required
                               class="form-input<?= isset($errores['clave2']) ? ' is-invalid' : '' ?>">
                        <?php if (isset($errores['clave2'])): ?>
                            <p class="form-error"><?= e($errores['clave2']) ?></p>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn-primary">Crear cuenta</button>
                    <a href="login.php" class="btn-secondary">Ya tengo cuenta</a>
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