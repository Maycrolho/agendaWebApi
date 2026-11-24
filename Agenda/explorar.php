<?php
require __DIR__ . '/sesion.php';
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


function partesFecha(string $fecha): array
{
    $dias  = ['DOM', 'LUN', 'MAR', 'MIÉ', 'JUE', 'VIE', 'SÁB'];
    $meses = ['ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC'];
    $t = strtotime($fecha);
    return [$dias[(int) date('w', $t)], date('d', $t), $meses[(int) date('n', $t) - 1]];
}


function mostrarEvento(array $ev): string
{
    [$dia, $num, $mes] = partesFecha($ev['fecha']);

    $html  = '<article class="card">';
    $html .= '<div class="fecha"><span>' . e($dia) . '</span><strong>' . e($num) . '</strong><small>' . e($mes) . '</small></div>';
    $html .= '<div class="evento-info">';
    $html .= '<h3>' . e($ev['titulo']) . '</h3>';

    if ($ev['descripcion']) {                      
        $html .= '<p>' . e($ev['descripcion']) . '</p>';
    }

    $html .= '<div class="evento-detalles">';
    if ($ev['hora']) {                              
        $html .= '<span>🕐 ' . e(date('g:i A', strtotime($ev['hora']))) . '</span>';
    }
    $html .= '<span>🏷 ' . e($ev['categoria']) . '</span>';
    $html .= '</div>';

    $id = (int) $ev['id'];                          
    $html .= '<div class="card__actions">'
           . '<a href="editar.php?id=' . $id . '" class="btn-secondary btn-sm">Editar</a>'
           . '<form method="post" action="borrar.php" class="form-inline">'
           . '<input type="hidden" name="id" value="' . $id . '">'
           . '<button type="submit" class="btn-danger btn-sm">Borrar</button>'
           . '</form></div>';

    $html .= '</div></article>';

    return $html;
}

// Arma el HTML del calendario de un mes con los eventos en su día
function generarCalendario(int $anio, int $mes, array $eventos): string
{
    $porDia = [];
    foreach ($eventos as $ev) {
        $porDia[$ev['fecha']][] = $ev;
    }

    $primero  = new DateTime(sprintf('%04d-%02d-01', $anio, $mes));
    $diasMes  = (int) $primero->format('t');
    $desfase  = (int) $primero->format('N') - 1;   // lunes = 0
    $hoy      = date('Y-m-d');

    $html = '<div class="cal-grid" role="grid">';
    foreach (['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'] as $d) {
        $html .= '<div class="cal-semana" role="columnheader">' . $d . '</div>';
    }

    for ($i = 0; $i < $desfase; $i++) {
        $html .= '<div class="cal-dia cal-vacio"></div>';
    }

    for ($dia = 1; $dia <= $diasMes; $dia++) {
        $fecha = sprintf('%04d-%02d-%02d', $anio, $mes, $dia);
        $clase = 'cal-dia' . ($fecha === $hoy ? ' cal-hoy' : '');
        $html .= '<div class="' . $clase . '" role="gridcell"><span class="cal-num">' . $dia . '</span>';

        $delDia = $porDia[$fecha] ?? [];
        foreach (array_slice($delDia, 0, 2) as $ev) {
            $hora = $ev['hora'] ? substr($ev['hora'], 0, 5) . ' ' : '';
            $html .= '<span class="cal-evento cat-' . (int) $ev['categoria_id'] . '" title="'
                   . e($hora . $ev['titulo']) . '">' . e($ev['titulo']) . '</span>';
        }
        if (count($delDia) > 2) {
            $html .= '<span class="cal-mas">+' . (count($delDia) - 2) . ' más</span>';
        }

        $html .= '</div>';
    }

    return $html . '</div>';
}


// Búsqueda por título (?buscar=...)
$buscar = trim($_GET['buscar'] ?? '');
if (longitud($buscar) > 120) {
    $buscar = mb_substr($buscar, 0, 120);
}

try {
    $sql = 'SELECT e.id, e.titulo, e.fecha, e.hora, e.categoria_id,
                   e.descripcion, c.nombre AS categoria
              FROM eventos e
              JOIN categorias c ON c.id = e.categoria_id
             WHERE e.usuario_id = ?';

    if ($buscar !== '') {
        $sql .= ' AND e.titulo LIKE ?';
    }
    $sql .= ' ORDER BY e.fecha, e.hora';

    $stmt = $mysqli->prepare($sql);

    if ($buscar !== '') {
        // Escapa % y _ para que el usuario los busque como texto normal,
        // y pone los comodines en la variable (no en el SQL).
        $q = '%' . addcslashes($buscar, '%_\\') . '%';
        $stmt->bind_param('is', $usuarioId, $q);
    } else {
        $stmt->bind_param('i', $usuarioId);
    }
    $stmt->execute();
    $eventos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // El filtro va en el ON (no en WHERE) para que las categorías sin eventos sigan en 0
    $stmt = $mysqli->prepare(
        'SELECT c.nombre, COUNT(e.id) AS total
           FROM categorias c
           LEFT JOIN eventos e ON e.categoria_id = c.id AND e.usuario_id = ?
          GROUP BY c.id, c.nombre
          ORDER BY c.nombre'
    );
    $stmt->bind_param('i', $usuarioId);
    $stmt->execute();
    $resumen = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $categorias = $mysqli->query(
        'SELECT id, nombre FROM categorias ORDER BY nombre'
    )->fetch_all(MYSQLI_ASSOC);

    $mysqli->close();
} catch (Throwable $ex) {
    die(DESARROLLO ? 'Error al leer los eventos: ' . $ex->getMessage() : 'Error al leer los eventos.');
}

// Mes que se muestra en el calendario (?mes=2026-10)
$mesParam = $_GET['mes'] ?? date('Y-m');
if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $mesParam)) {
    $mesParam = date('Y-m');
}
$mesActual  = new DateTime($mesParam . '-01');
$mesPrevio  = (clone $mesActual)->modify('-1 month')->format('Y-m');
$mesSiguiente = (clone $mesActual)->modify('+1 month')->format('Y-m');
$nombresMes = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio',
               'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$tituloMes = ucfirst($nombresMes[(int) $mesActual->format('n') - 1]) . ' ' . $mesActual->format('Y');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>AgendaWeb</title>

    <script src="tema.js"></script>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@600;700&display=swap" rel="stylesheet">

  
    <link rel="stylesheet" href="../css/style.css">
</head>

<body>

    <header class="header">
        <div class="contenedor navbar">

            <a href="#" class="logo">AgendaWeb</a>

            <nav class="nav">
                <a href="#inicio">Inicio</a>
                <a href="#agenda">Agenda</a>
                <a href="explorar.php">Explorar</a>
                <a href="#calendario">Calendario</a>
                <a href="#contacto">Contacto</a>
            </nav>

            <button class="tema-toggle" id="alternarTema" type="button" aria-label="Activar modo oscuro">
                <span aria-hidden="true">🌙</span>
                <span class="tema-texto">Oscuro</span>
            </button>

            <a href="logout.php" class="nav__link">Salir</a>

            <button class="btn btn-pequeno" id="abrirModal">
                + Crear evento
            </button>

        </div>
    </header>


  
    <main>

      
        <section class="hero" id="inicio">

            <div class="contenedor hero-contenido">

                <div class="hero-texto">

                    <span class="badge">
                        Tu tiempo, organizado
                    </span>

                    <h1>
                        Organiza tu día.<br>
                        Vive mejor.
                    </h1>

                    <p>
                        Agenda tus eventos, citas y actividades
                        en un solo lugar. Sencillo, elegante y
                        fácil de usar.
                    </p>

                    <div class="hero-botones">

                        <a href="#agenda" class="btn btn-principal">
                            Ver mi agenda
                        </a>

                        <button
                            class="btn btn-secundario"
                            id="abrirModalHero"
                        >
                            + Crear evento
                        </button>

                    </div>

                </div>

            </div>

        </section>


       
        <section class="agenda" id="agenda">

            <div class="contenedor">

                <div class="seccion-encabezado">

                    <div>
                        <span class="etiqueta">
                            PRÓXIMOS EVENTOS
                        </span>

                        <h2>
                            Mi agenda
                        </h2>

                        <p class="contador">
                            <?php if ($buscar !== ''): ?>
                                <?= count($eventos) ?> resultado(s) para «<?= e($buscar) ?>»
                            <?php else: ?>
                                <?= count($eventos) ?> eventos registrados
                            <?php endif; ?>
                        </p>
                        <p class="resumen">
                            <?php foreach ($resumen as $r): ?>
                                <span class="card__badge"><?= e($r['nombre']) ?> · <?= (int) $r['total'] ?></span>
                            <?php endforeach; ?>
                        </p>
                    </div>

                    <button
                        class="btn btn-principal"
                        id="abrirModalAgenda"
                    >
                        + Nuevo evento
                    </button>

                </div>


                <!-- BUSCADOR -->
                <form method="get" action="#agenda" class="buscador">
                    <input type="text" name="buscar" maxlength="120"
                           placeholder="Buscar evento por título..."
                           value="<?= e($buscar) ?>" aria-label="Buscar evento">
                    <button type="submit" class="btn btn-principal">Buscar</button>
                    <?php if ($buscar !== ''): ?>
                        <a href="index.php#agenda" class="btn btn-secundario">Limpiar</a>
                    <?php endif; ?>
                </form>


                <!-- AVISO: solo si registrar.php mandó ?ok=1 -->
                <?php if (isset($_GET['ok'])): ?>
                    <div class="mensaje mensaje--visible" role="status">
                        Evento guardado correctamente.
                    </div>
                <?php endif; ?>


                <!-- LISTA DE EVENTOS -->
                <div class="lista-eventos" id="listaEventos">

                    <?php if (empty($eventos) && $buscar !== ''): ?>
                        <div class="vacio">
                            <strong>Sin resultados</strong>
                            <p>No hay eventos cuyo título contenga «<?= e($buscar) ?>».</p>
                        </div>
                    <?php elseif (empty($eventos)): ?>
                        <div class="vacio">
                            <strong>Aún no tienes eventos</strong>
                            <p>Agrega el primero para verlo aquí.</p>
                            <a href="registrar.php" class="btn btn-principal">Crear evento</a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($eventos as $ev): ?>
                            <?= mostrarEvento($ev) ?>
                        <?php endforeach; ?>
                    <?php endif; ?>

                </div>

            </div>

        </section>


        <!-- CALENDARIO -->
        <section class="seccion-extra calendario" id="calendario">

            <div class="contenedor">

                <div class="cal-encabezado">

                    <div>
                        <span class="etiqueta">CALENDARIO</span>
                        <h2><?= e($tituloMes) ?></h2>
                    </div>

                    <div class="cal-nav">
                        <a class="btn btn-secundario" href="?mes=<?= $mesPrevio ?>#calendario" aria-label="Mes anterior">←</a>
                        <a class="btn btn-secundario" href="?mes=<?= date('Y-m') ?>#calendario">Hoy</a>
                        <a class="btn btn-secundario" href="?mes=<?= $mesSiguiente ?>#calendario" aria-label="Mes siguiente">→</a>
                    </div>

                </div>

                <?= generarCalendario((int) $mesActual->format('Y'), (int) $mesActual->format('n'), $eventos) ?>

            </div>

        </section>


        <!-- CONTACTO -->
        <section class="seccion-extra" id="contacto">

            <div class="contenedor">

                <span class="etiqueta">
                    CONTACTO
                </span>

                <h2>
                    AgendaWeb
                </h2>

                <p>
                    Una forma sencilla de organizar
                    tus actividades.
                </p>

            </div>

        </section>

    </main>


    <div class="modal" id="modal">

        <div class="modal-contenido">

            <button
                class="cerrar"
                id="cerrarModal"
                type="button"
            >
                ×
            </button>

            <span class="etiqueta">
                NUEVO EVENTO
            </span>

            <h2>
                Crear evento
            </h2>

            <p class="modal-descripcion">
                Completa la información de tu evento.
            </p>


            <form method="post" action="registrar.php">

                <div class="campo">
                    <label for="titulo">Nombre del evento</label>
                    <input type="text" id="titulo" name="titulo" maxlength="120"
                           placeholder="Ej. Reunión de academia" required>
                </div>

                <div class="fila">
                    <div class="campo">
                        <label for="fecha">Fecha</label>
                        <input type="date" id="fecha" name="fecha" required>
                    </div>

                    <div class="campo">
                        <label for="hora">Hora (opcional)</label>
                        <input type="time" id="hora" name="hora">
                    </div>
                </div>

                <div class="campo">
                    <label for="categoria_id">Categoría</label>
                    <select id="categoria_id" name="categoria_id" class="form-select" required>
                        <option value="">— Elige una —</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?= (int) $cat['id'] ?>"><?= e($cat['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo">
                    <label for="descripcion">Descripción (opcional)</label>
                    <textarea id="descripcion" name="descripcion" rows="4" maxlength="500"
                              placeholder="Agrega una descripción..."></textarea>
                </div>

                <div class="modal-botones">
                    <button type="button" class="btn btn-cancelar" id="cancelar">Cancelar</button>
                    <button type="submit" class="btn btn-principal">Guardar evento</button>
                </div>

            </form>

        </div>

    </div>


  
    <footer class="footer">

        <div class="contenedor">

            <p>
                © 2026 AgendaWeb · Organiza tu tiempo.
            </p>

        </div>

    </footer>


    <!-- JAVASCRIPT -->
    <script src="script.js"></script>

</body>
</html>