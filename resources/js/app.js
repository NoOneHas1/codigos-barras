import './bootstrap';
import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;

document.addEventListener("DOMContentLoaded", () => {

    // ============================
    // ELEMENTOS
    // ============================
    const archivoInput   = document.getElementById("archivo");
    const btnImportar    = document.getElementById("btnImportar");
    const realInput      = document.getElementById("archivoRealInput");
    const mostrarNombre  = document.getElementById("archivoNombreMostrar");
    const nombreExport   = document.getElementById("nombreExportadoInput");
    const modalEl        = document.getElementById("modalImportarArchivo");
    const simpleOverlay  = document.getElementById("simpleOverlay");

    // ============================
    // LIMPIAR INPUTS AL CARGAR
    // ============================
    if (realInput) realInput.value = "";
    if (mostrarNombre) mostrarNombre.value = "";
    if (nombreExport) nombreExport.value = "";

    // ============================
    // ABRIR MODAL IMPORTAR
    // ============================
    btnImportar.addEventListener("click", () => {
        if (!archivoInput.files.length) {
            return showToast("Selecciona un archivo primero", "warning");
        }

        const file = archivoInput.files[0];
        mostrarNombre.value = file.name;

        // Pasar archivo al input real del modal
        let dt = new DataTransfer();
        dt.items.add(file);
        realInput.files = dt.files;

        new bootstrap.Modal(modalEl).show();
    });

    // ============================
    // LIMPIAR MODAL AL CERRAR
    // ============================
    if (modalEl) {
        modalEl.addEventListener('hidden.bs.modal', () => {
            realInput.value = "";
            mostrarNombre.value = "";
            nombreExport.value = "";
        });
    }

    // ============================
    // MOSTRAR OVERLAY AL ENVIAR
    // ============================
    const modalForm = document.querySelector('#modalImportarArchivo form');
    if (modalForm) {
        modalForm.addEventListener('submit', () => {
            // Ocultar modal
            const modal = bootstrap.Modal.getInstance(modalEl);
            modal.hide();

            // Mostrar overlay simple
            simpleOverlay.style.display = 'flex';
        });
    }

    // ============================
    // TOASTS
    // ============================
    const toastElList = [].slice.call(document.querySelectorAll('.toast'));
    toastElList.map(function (toastEl) {
        let toast = new bootstrap.Toast(toastEl, { delay: 4000 });
        toast.show();
    });

    window.showToast = function (message, type = "info") {
        const bg = {
            success: "text-bg-success",
            error: "text-bg-danger",
            warning: "text-bg-warning",
            info: "text-bg-primary"
        }[type];

        const toastHTML = `
            <div class="toast align-items-center ${bg} border-0 mb-2" role="alert">
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;

        const container = document.querySelector(".toast-container");
        container.insertAdjacentHTML("beforeend", toastHTML);

        let toastEl = container.lastElementChild;
        new bootstrap.Toast(toastEl, { delay: 4000 }).show();
    };
    
});
