<?php
session_start();
if (isset($_SESSION['uid'])) {
    header('Location: index.php');
    exit;
}
require __DIR__ . '/conexion.php';

function e(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}

function correoNormalizado(string $correo): string
{
    return function_exists('mb_strtolower')
        ? mb_strtolower($correo, 'UTF-8')
        : strtolower($correo);
}

$correo = '';
$error  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = correoNormalizado(trim($_POST['correo'] ?? ''));
    $clave  = $_POST['clave'] ?? '';

    // 1 · Buscar al usuario por su correo
    $stmt = $mysqli->prepare('SELECT id, nombre, clave_hash FROM usuarios WHERE correo = ?');
    $stmt->bind_param('s', $correo);
    $stmt->execute();
    $usuario = $stmt->get_result()->fetch_assoc();   // null si no existe
    $stmt->close();

    // 2 · ¿Existe y la contraseña coincide con su hash?
    if ($usuario && password_verify($clave, $usuario['clave_hash'])) {
        session_regenerate_id(true);                  // id de sesión nuevo al entrar
        $_SESSION['uid']    = (int) $usuario['id'];
        $_SESSION['nombre'] = $usuario['nombre'];
        header('Location: index.php');
        exit;
    }

    // 3 · Mismo mensaje en los dos casos: no revelamos si el correo existe
    $error = 'Correo o contraseña incorrectos.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión - AgendaWeb</title>
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
                    <span class="etiqueta">ACCESO</span>
                    <h1>Iniciar sesión</h1>
                    <p>Entra para ver tu agenda.</p>
                </div>

                <?php if (isset($_GET['registrado'])): ?>
                    <div class="alert alert--ok">Cuenta creada. Ya puedes iniciar sesión.</div>
                <?php endif; ?>
                <?php if (isset($_GET['salio'])): ?>
                    <div class="alert alert--ok">Cerraste sesión. ¡Hasta pronto!</div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert--error"><?= e($error) ?></div>
                <?php endif; ?>

                <form method="post" action="" novalidate>
                    <div class="form-group">
                        <label class="form-label" for="correo">Correo</label>
                        <input type="email" name="correo" id="correo" class="form-input" required
                               value="<?= e($correo) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="clave">Contraseña</label>
                        <input type="password" name="clave" id="clave" class="form-input"
                               autocomplete="current-password" required>
                    </div>

                    <button type="submit" class="btn-primary">Entrar</button>
                    <a href="Registro.php" class="btn-secondary">Crear cuenta</a>
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