import './bootstrap';
import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;


document.addEventListener("DOMContentLoaded", () => {

    const archivoInput   = document.getElementById("archivo");
    const loteSelect     = document.getElementById("loteSelect");
    const loaderBar      = document.getElementById("loaderBar");
    const loaderProgress = document.getElementById("loaderProgress");

    const btnImportar = document.getElementById("btnImportar");
    const btnExportar = document.getElementById("btnExportar");
    const btnLimpiar  = document.getElementById("btnLimpiar");
    const btnTutorial = document.getElementById("btnTutorial");

    // ========== RUTAS PASADAS DESDE BLADE ==========
    const importarUrl = window.appConfig.importarUrl;
    const exportarUrl = window.appConfig.exportarUrl;
    const indexUrl    = window.appConfig.indexUrl;
    const csrfToken   = window.appConfig.csrfToken;

    let cargando = false;

    // ===================================================
    // MODAL TUTORIAL
    // ===================================================
    const tutorialModalEl = document.getElementById('tutorialModal');
    const tutorialBody = document.getElementById('tutorialBody');
    const tutorialAccept = document.getElementById('tutorialAccept');

    if (tutorialModalEl && tutorialBody && tutorialAccept && window.bootstrap) {
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

    // ===================================================
    // DESHABILITAR EXPORTAR SI NO HAY LOTE SELECCIONADO
    // ===================================================
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

    // ===================================================
    // EDITAR NOMBRE LOTE
    // ===================================================
    // Cargar el modal con el nombre actual
    document.getElementById('editLoteModal').addEventListener('show.bs.modal', function() {
        const nombreActual = document.getElementById('nombreLote').textContent.replace('Lote ', '');
        document.getElementById('nuevoNombreLote').value = nombreActual;
    });

    // Guardar nuevo nombre
document.getElementById('btnGuardarNombreLote').addEventListener('click', function() {
    const nuevoNombre = document.getElementById('nuevoNombreLote').value.trim();
    
    if (!nuevoNombre) {
        showToast('Ingresa un nombre válido', 'warning');
        return;
    }

    fetch(window.appConfig.actualizarNombreUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': window.appConfig.csrfToken
        },
        body: JSON.stringify({
            lote_id: window.appConfig.loteId,
            nombre: nuevoNombre
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            document.getElementById('nombreLote').textContent = 'Lote: ' + nuevoNombre;
            bootstrap.Modal.getInstance(document.getElementById('editLoteModal')).hide();
            showToast('Nombre actualizado correctamente', 'success');
        } else {
            showToast('Error: ' + (data.message || 'No se pudo actualizar'), 'error');
        }
    })
    .catch(err => {
        console.error('Error:', err);
        showToast('Error en la solicitud', 'error');
    });
});

    validarExportar();
    loteSelect.addEventListener("change", validarExportar);

    // ===================================================
    // CAMBIAR DE LOTE
    // ===================================================
    loteSelect.onchange = () => {
        const lote = loteSelect.value;
        if (!lote || cargando) return;
        window.location.href = indexUrl + "?lote=" + lote;
    };

    // ===================================================
    // IMPORTAR ARCHIVO
    // ===================================================
    btnImportar.onclick = () => {

        if (cargando) return;

        if (!archivoInput.files.length) {
            showToast("Selecciona un archivo Excel antes de importar.", 'warning');
            return;
        }

        const archivo = archivoInput.files[0];
        const tiposExcel = [
            "application/vnd.ms-excel",
            "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
            "application/vnd.ms-excel.sheet.macroEnabled.12",
            "application/vnd.ms-excel.sheet.binary.macroEnabled.12"
        ];

        const extensionesExcel = [".xls", ".xlsx", ".xlsm", ".xlsb"];
        let extension = archivo.name.substring(archivo.name.lastIndexOf(".")).toLowerCase();

        if (!tiposExcel.includes(archivo.type) && !extensionesExcel.includes(extension)) {
            showToast("Solo se permiten archivos de Excel (.xls, .xlsx, .xlsm, .xlsb).", 'warning');
            archivoInput.value = "";
            return;
        }

        cargando = true;

        btnImportar.disabled = true;
        btnExportar.disabled = true;
        btnLimpiar.disabled  = true;
        loteSelect.disabled  = true;
        archivoInput.disabled = true;

        let formData = new FormData();
        formData.append("archivo", archivoInput.files[0]);
        formData.append("_token", csrfToken);

        loaderBar.style.display = "none"; // aseguramos que esté oculto al inicio
        loaderBar.style.display = "block";
        loaderProgress.style.width = "20%";

        fetch(importarUrl, {
            method: "POST",
            body: formData
        })
        .then(response => {

            loaderProgress.style.width = "60%";
            const redirectURL = response.url;
            loaderProgress.style.width = "100%";

            setTimeout(() => {
                window.location.href = redirectURL;
            }, 400);

        })
        .catch(() => {
            showToast('Error en la solicitud', 'error');
            cargando = false;

            btnImportar.disabled = false;
            btnLimpiar.disabled  = false;
            loteSelect.disabled  = false;
            archivoInput.disabled = false;

            validarExportar();
            
        });
            
        
    };

    // ===================================================
    // EXPORTAR LOTE
    // ===================================================
    btnExportar.onclick = () => {

        if (cargando) return;

        const lote = loteSelect.value;

        if (!lote) {
            showToast("Selecciona un lote para exportar.", 'warning');
            return;
        }

        window.location.href = exportarUrl + "?lote_id=" + lote;
    };

    // ===================================================
    // LIMPIAR VISTA
    // ===================================================
    btnLimpiar.onclick = () => {

        if (cargando) return;

        archivoInput.value = "";
        loteSelect.selectedIndex = 0;

        window.location.href = indexUrl;
    };

    // ===================================================
    //TOASTS
    // ===================================================
    function showToast(message, type = 'info', delay = 3500) {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const id = 'toast-' + Date.now();
    let bgClass = 'bg-primary text-white';
    let closeClass = 'btn-close btn-close-white';

    if (type === 'success') { bgClass = 'bg-success text-white'; closeClass = 'btn-close btn-close-white'; }
    if (type === 'error')   { bgClass = 'bg-danger text-white';  closeClass = 'btn-close btn-close-white'; }
    if (type === 'warning') { bgClass = 'bg-warning text-dark';  closeClass = 'btn-close'; }
    if (type === 'info')    { bgClass = 'bg-primary text-white'; closeClass = 'btn-close btn-close-white'; }

    const toastEl = document.createElement('div');
    toastEl.className = 'toast ' + bgClass;
    toastEl.setAttribute('role', 'alert');
    toastEl.setAttribute('aria-live', 'assertive');
    toastEl.setAttribute('aria-atomic', 'true');
    toastEl.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="${closeClass} me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;

    container.appendChild(toastEl);
    const bsToast = new bootstrap.Toast(toastEl, { autohide: true, delay: delay });
    bsToast.show();

    toastEl.addEventListener('hidden.bs.toast', () => {
        toastEl.remove();
    });
}


}); // Cierre DOMContentLoaded
