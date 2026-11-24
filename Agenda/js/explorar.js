// Los huecos de la página (los id de explorar.php)
const lista    = document.getElementById('lista');
const estado   = document.getElementById('estado');
const campoQ   = document.getElementById('q');
const campoCat = document.getElementById('categoria');

const DIAS  = ['DOM', 'LUN', 'MAR', 'MIÉ', 'JUE', 'VIE', 'SÁB'];
const MESES = ['ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC'];

// Crea un elemento con clase y texto (textContent: nunca interpreta HTML)
function el(etiqueta, clase, texto) {
    const nodo = document.createElement(etiqueta);
    if (clase) nodo.className = clase;
    if (texto !== undefined) nodo.textContent = texto;
    return nodo;
}

// Crea UNA tarjeta a partir de UN evento del JSON (mismo diseño que index.php)
function crearTarjeta(ev) {
    const [a, m, d] = ev.fecha.split('-').map(Number);
    const f = new Date(a, m - 1, d);

    const tarjeta = el('article', 'card');

    const fecha = el('div', 'fecha');
    fecha.append(
        el('span', '', DIAS[f.getDay()]),
        el('strong', '', String(d).padStart(2, '0')),
        el('small', '', MESES[m - 1])
    );

    const info = el('div', 'evento-info');
    info.append(el('h3', '', ev.titulo));

    if (ev.descripcion) {                    // null o '' → no se pinta
        info.append(el('p', '', ev.descripcion));
    }

    const detalles = el('div', 'evento-detalles');
    if (ev.hora) {                           // null → no se pinta
        detalles.append(el('span', '', '🕐 ' + ev.hora));
    }
    detalles.append(el('span', '', '🏷 ' + ev.categoria));
    info.append(detalles);

    const acciones = el('div', 'card__actions');
    const editar = el('a', 'btn-secondary btn-sm', 'Editar');
    editar.href = 'editar.php?id=' + encodeURIComponent(ev.id);
    acciones.append(editar);
    info.append(acciones);

    tarjeta.append(fecha, info);
    return tarjeta;
}

// Pinta la lista completa
function pintar(datos) {
    lista.replaceChildren();
    const buscando = datos.filtros.q !== '' || datos.filtros.categoria !== 0;

    if (datos.total === 0) {
        estado.textContent = buscando ? 'Sin resultados para tu búsqueda.'
                                      : 'Aún no tienes eventos registrados.';
        return;
    }
    const s = datos.total === 1 ? '' : 's';
    estado.textContent = `${datos.total} evento${s} `
                       + (buscando ? `encontrado${s}` : `registrado${s}`);
    datos.eventos.forEach(ev => lista.append(crearTarjeta(ev)));
}

async function cargarEventos() {
    const params = new URLSearchParams({ q: campoQ.value.trim(), categoria: campoCat.value });
    estado.textContent = 'Cargando eventos…';

    try {
        const res = await fetch('api.php?' + params);    // 1 · pedir

        if (res.status === 401) {                        // 2 · revisar
            estado.innerHTML = 'Tu sesión terminó. <a href="login.php">Inicia sesión</a> de nuevo.';
            lista.replaceChildren();
            return;
        }
        if (!res.ok) {
            throw new Error('La API respondió ' + res.status);
        }

        const datos = await res.json();                  // 3 · convertir
        pintar(datos);                                   // 4 · pintar
    } catch (error) {
        // Sin red, servidor caído o JSON roto
        console.error(error);
        estado.textContent = 'No se pudieron cargar los eventos. Intenta de nuevo más tarde.';
        lista.replaceChildren();
    }
}

// Espera 300 ms a que dejes de teclear antes de pedir (debounce)
let espera;
campoQ.addEventListener('input', () => {
    clearTimeout(espera);
    espera = setTimeout(cargarEventos, 300);
});
campoCat.addEventListener('change', cargarEventos);

// Si presionan Enter, que no se recargue la página
document.getElementById('filtros').addEventListener('submit', e => e.preventDefault());

cargarEventos();                                         // primera carga