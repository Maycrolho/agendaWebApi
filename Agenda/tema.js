// Aplica el tema lo antes posible (se carga en el <head>) para evitar parpadeo
let temaGuardado = null;
try {
    temaGuardado = localStorage.getItem("agenda-tema");
} catch (e) {}

if (!temaGuardado) {
    temaGuardado = window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches
        ? "dark"
        : "light";
}
document.documentElement.dataset.theme = temaGuardado === "dark" ? "dark" : "light";

document.addEventListener("DOMContentLoaded", function () {
    const botonTema = document.getElementById("alternarTema");
    if (!botonTema) {
        return;
    }

    function actualizarBotonTema() {
        const esOscuro = document.documentElement.dataset.theme === "dark";
        botonTema.setAttribute(
            "aria-label",
            esOscuro ? "Activar modo claro" : "Activar modo oscuro"
        );
        botonTema.innerHTML = esOscuro
            ? '<span aria-hidden="true">☀️</span><span class="tema-texto">Claro</span>'
            : '<span aria-hidden="true">🌙</span><span class="tema-texto">Oscuro</span>';
    }

    actualizarBotonTema();
    botonTema.addEventListener("click", function () {
        const nuevoTema = document.documentElement.dataset.theme === "dark" ? "light" : "dark";
        document.documentElement.dataset.theme = nuevoTema;
        try {
            localStorage.setItem("agenda-tema", nuevoTema);
        } catch (e) {}
        actualizarBotonTema();
    });
});