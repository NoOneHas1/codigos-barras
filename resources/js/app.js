import './bootstrap';
import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;

console.log("APP JS CARGADO");

document.addEventListener("DOMContentLoaded", () => {

    // ============================
    // ELEMENTOS PRINCIPALES
    // ============================
    const archivoInput   = document.getElementById("archivo");
    const loteSelect     = document.getElementById("loteSelect");
    const btnImportar    = document.getElementById("btnImportar");
    const btnExportar    = document.getElementById("btnExportar");
    const btnLimpiar     = document.getElementById("btnLimpiar");
    const btnTutorial    = document.getElementById("btnTutorial");
    const btnEditarLote  = document.getElementById("btnEditarLote");
    const btnEliminarLote = document.getElementById("btnEliminarLote");

    // Rutas
    const importarUrl = window.appConfig.importarUrl;
    const exportarUrl = window.appConfig.exportarUrl;
    const indexUrl    = window.appConfig.indexUrl;
    const csrfToken   = window.appConfig.csrfToken;

    let cargando = false;


    // ============================
    // MODAL TUTORIAL
    // ============================
    const tutorialModalEl = document.getElementById('tutorialModal');
    const tutorialBody = document.getElementById('tutorialBody');
    const tutorialAccept = document.getElementById('tutorialAccept');

    if (tutorialModalEl && tutorialBody && tutorialAccept) {
        const tutorialModal = new bootstrap.Modal(tutorialModalEl, { backdrop: 'static', keyboard: false });

        function checkScrollEnd() {
            const atBottom = tutorialBody.scrollTop + tutorialBody.clientHeight >= tutorialBody.scrollHeight - 2;
            tutorialAccept.disabled = !atBottom;
        }

        tutorialBody.addEventListener("scroll", checkScrollEnd);

        btnTutorial.addEventListener("click", () => {
            tutorialAccept.disabled = true;
            tutorialBody.scrollTop = 0;
            tutorialModal.show();
            checkScrollEnd();
        });

        tutorialAccept.addEventListener("click", () => {
            tutorialModal.hide();
        });
    }

    // ============================
    // DESHABILITAR EXPORTAR SI NO HAY LOTE
    // ============================
    function validarExportar() {
        if (!loteSelect.value) {
            btnExportar.disabled = true;
            btnExportar.style.opacity = "0.5";
            btnExportar.style.cursor = "not-allowed";
        } else {
            btnExportar.disabled = false;
            btnExportar.style.opacity = "1";
            btnExportar.style.cursor = "pointer";
        }
    }
    validarExportar();
    loteSelect.addEventListener("change", validarExportar);

    // ============================
    // CAMBIAR LOTE
    // ============================
    loteSelect.onchange = () => {
        const lote = loteSelect.value;
        if (!lote || cargando) return;
        window.location.href = indexUrl + "?lote=" + lote;
    };

    // ============================
    // EXPORTAR LOTE
    // ============================
    btnExportar.onclick = () => {
        const lote = loteSelect.value;
        if (!lote) return Swal.fire("Selecciona un lote", "", "warning");
        window.location.href = exportarUrl + "?lote_id=" + lote;
    };

    // ============================
    // ELIMINAR LOTE → ABRIR MODAL
    // ============================
    btnEliminarLote.onclick = () => {
        const loteSeleccionado = loteSelect.value;

        if (!loteSeleccionado) {
            alert("Selecciona un lote primero.");
            return;
        }

        // Insertamos nombre del lote en el modal
        document.getElementById("loteAEliminarTexto").innerText = loteSeleccionado;
        document.getElementById("loteAEliminar").value = loteSeleccionado;

        // Abrimos modal
        const modal = new bootstrap.Modal(document.getElementById("modalEliminarLote"));
        modal.show();
    };


    // ============================
    // IMPORTAR ARCHIVO → ABRIR MODAL
    // ============================
    btnImportar.addEventListener("click", () => {

        if (!archivoInput.files.length) {
            return Swal.fire("Selecciona un archivo", "", "warning");
        }

        const file = archivoInput.files[0];

        // Si NO hay lote seleccionado → se usa el nombre del archivo
        if (!loteSelect.value) {
            document.getElementById("loteNameInput").value = file.name.replace(/\.[^/.]+$/, "");
        } else {
            // Si hay lote seleccionado, se usa ese lote
            document.getElementById("loteNameInput").value = loteSelect.value;
        }

        document.getElementById("archivoNombreMostrar").value = file.name;

        const realInput = document.getElementById("archivoRealInput");
        let dt = new DataTransfer();
        dt.items.add(file);
        realInput.files = dt.files;

        new bootstrap.Modal(document.getElementById("modalImportarArchivo")).show();
    });

    // ============================
    // EDITAR LOTE
    // ============================
    btnEditarLote.addEventListener("click", () => {
        let lote = loteSelect.value;
        if (!lote) return Swal.fire("Selecciona un lote", "", "warning");

        document.getElementById("lote_old").value = lote;
        new bootstrap.Modal(document.getElementById("modalEditarLote")).show();
    });
});