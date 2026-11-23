const modal = document.getElementById("modal");

const abrirModal = document.getElementById("abrirModal");
const abrirModalHero = document.getElementById("abrirModalHero");
const abrirModalAgenda = document.getElementById("abrirModalAgenda");

const cerrarModal = document.getElementById("cerrarModal");
const cancelar = document.getElementById("cancelar");


function mostrarModal() {
    modal.classList.add("activo");
    document.getElementById("titulo").focus();
}

function ocultarModal() {
    modal.classList.remove("activo");
}


abrirModal.addEventListener("click", mostrarModal);
abrirModalHero.addEventListener("click", mostrarModal);
abrirModalAgenda.addEventListener("click", mostrarModal);

cerrarModal.addEventListener("click", ocultarModal);
cancelar.addEventListener("click", ocultarModal);


// Clic en el fondo oscuro cierra el modal
modal.addEventListener("click", function (evento) {
    if (evento.target === modal) {
        ocultarModal();
    }
});

// Tecla Escape cierra el modal
document.addEventListener("keydown", function (evento) {
    if (evento.key === "Escape") {
        ocultarModal();
    }
});