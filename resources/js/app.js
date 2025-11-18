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
            alert("⚠️ Selecciona un archivo Excel antes de importar.");
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
            alert("❌ Solo se permiten archivos de Excel (.xls, .xlsx, .xlsm, .xlsb).");
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
            alert("❌ Error al procesar archivo.");
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
            alert("⚠️ Selecciona un lote para exportar.");
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

}); // Cierre DOMContentLoaded
