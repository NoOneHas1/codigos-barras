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
    const overlay        = document.getElementById("overlayLoader");
    const formImportar   = modalEl.querySelector("form");
    const btnExportar    = document.getElementById("btnExportar");
    const formExport     = document.getElementById("formExport");

    // ============================
    // LIMPIAR INPUTS AL CARGAR
    // ============================
    if (realInput) realInput.value = "";
    if (mostrarNombre) mostrarNombre.value = "";
    if (nombreExport) nombreExport.value = "";

    // ============================
    //MODAL TUTORIAL
    // ============================
    const btnTutorial = document.getElementById('btnTutorial');
const modalTutorialEl = document.getElementById('modalTutorial');

btnTutorial.addEventListener('click', () => {
    new bootstrap.Modal(modalTutorialEl).show();
});


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
    const dt = new DataTransfer();
    dt.items.add(file);
    realInput.files = dt.files;

    // SOLO ABRIR EL MODAL
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
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
    // OVERLAY IMPORTACIÓN
    // ============================
    formImportar.addEventListener("submit", () => {
        // Ocultar modal
        const modalInstance = bootstrap.Modal.getInstance(modalEl);
        modalInstance.hide();
        overlay.classList.remove('d-none');
    });

    // ============================
    // EXPORTACIÓN CON OVERLAY
    // ============================
    formExport.addEventListener("submit", function(e){
        e.preventDefault(); // evitar submit normal
        overlay.classList.remove('d-none');

        fetch(appConfig.exportarUrl, {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': appConfig.csrfToken
            }
        })
        .then(res => {
            if (!res.ok) throw new Error("Error exportando archivo");
            return res.blob();
        })
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            // nombre dinámico basado en server
            let timestamp = new Date().toISOString().replace(/[-:.]/g, "");
            a.download = `documentos_${timestamp}.xlsx`;
            document.body.appendChild(a);
            a.click();
            a.remove();
            window.URL.revokeObjectURL(url);
            overlay.classList.add('d-none');
        })
        .catch(err => {
            overlay.classList.add('d-none');
            showToast(err.message || "Error exportando archivo", "error");
        });
    });

    //OVERLAY LIMPIAR
    const formLimpiar = document.getElementById("formLimpiar");

        if (formLimpiar) {
            formLimpiar.addEventListener("submit", () => {
                overlay.classList.remove('d-none');
            });
}

// ============================
// CONFIRMAR LIMPIAR
// ============================

document.getElementById('confirmarLimpiar').addEventListener('click', () => {
    new bootstrap.Modal(document.getElementById('modalConfirmarLimpiar')).show();
});

document.getElementById('btnLimpiar').addEventListener('click', () => {
    document.getElementById('formLimpiar').submit();
});


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
