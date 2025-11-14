@extends('layouts.app')

@section('title', 'Listado de Documentos')

@section('content')

<!-- Modal-tutorial -->
<div class="modal fade" id="tutorialModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">¿Como generar códigos de barras?</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body" id="tutorialBody" style="max-height: 50vh; overflow-y: auto;">
        <p><strong>Paso 1:</strong> Selecciona el archivo Excel que quieres importar.</p>
        <p><strong>Paso 2:</strong> Haz clic en <em>Importar</em> y espera a que termine el proceso.</p>
        <p><strong>Paso 3:</strong> A continuación se cargaran todos los datos del excel y se mostraran en pantalla</p>
        <p><strong>Nota:</strong> Puedes seleccionar el lote que deseas exportar.</p>
        <p><strong>Paso 4:</strong> Usa <em>Exportar</em> para bajar el lote seleccionado.</p>
        <p><strong>Paso 5:</strong> Se descargara un Excel con los datos y el codigo de barras generado</p>
        <hr>
      </div>
      <div class="modal-footer">
        <button type="button" id="tutorialAccept" class="btn btn-primary" disabled>Aceptar</button>
      </div>
    </div>
  </div>
</div>

{{-- ======================================
     BOTONES Y FORMULARIOS
======================================= --}}
<div class="d-flex gap-2 mb-4 align-items-center flex-wrap">

    {{-- INPUT ARCHIVO --}}
    <input type="file" id="archivo" class="form-control"
        style="height: 38px; padding: 6px 12px; flex: 1; min-width: 200px;"
        accept=".xls,.xlsx,.xlsm,.xlsb,.xlt,.xltm,.xltx,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet">

    {{-- BOTÓN IMPORTAR --}}
    <button id="btnImportar" class="btn btn-primary" style="height: 38px; padding: 6px 20px; white-space: nowrap;">
        Importar
    </button>

    {{-- SELECT DE LOTES --}}
    <select id="loteSelect" class="form-select" style="height: 38px; padding: 6px 12px; max-width: 150px;">
        <option value="" selected disabled>Lote</option>
        @foreach ($lotes as $l)
            <option value="{{ $l }}" {{ $l == $lote ? 'selected' : '' }}>
                Lote {{ $l }}
            </option>
        @endforeach
    </select>

    {{-- EXPORTAR --}}
    <button id="btnExportar" class="btn btn-success" style="height: 38px; padding: 6px 20px; white-space: nowrap;">
        Exportar
    </button>

    {{-- LIMPIAR --}}
    <button id="btnLimpiar" class="btn btn-secondary" style="height: 38px; padding: 6px 20px; white-space: nowrap;">
        Limpiar
    </button>

</div>

{{-- ======================================
     BARRA DE CARGA MODERNA
======================================= --}}
<div id="loaderBar">
    <div class="progress">
        <div class="progress-bar progress-bar-striped progress-bar-animated"
             id="loaderProgress" style="width:0%"></div>
    </div>
    <small class="text-muted">Procesando archivo…</small>
</div>

{{-- ======================================
     TABLA DE DOCUMENTOS
======================================= --}}
<div class="card mt-4">
    <div class="card-header fw-bold">
        <p class="subtitle">
            Lote {{ $lote }}
        </p>
    </div>

    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead class="table-header-custom">
            <tr>
                <th>Tipo</th>
                <th>Número</th>
                <th>Nombre</th>
                <th>Código</th>
                <th>Fecha</th>
            </tr>
            </thead>

            <tbody>
            @forelse($documentos as $doc)
                <tr>
                    <td>{{ $doc->tipo_doc }}</td>
                    <td>{{ $doc->numero_doc }}</td>
                    <td>{{ $doc->nombre }}</td>
                    <td>
                        <img src="{{ asset('storage/' . $doc->codigo_path) }}"
                             height="40">
                    </td>
                    <td>{{ $doc->created_at->format('Y-m-d H:i') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center p-3">
                        No hay documentos en este lote
                    </td>
                </tr>
            @endforelse
            </tbody>

        </table>
    </div>
</div>

{{-- Botón Tutorial --}}
<div class="text-center mt-4">
    <p>¿Necesitas ayuda?</p>
    <button id="btnTutorial" class="btn-tutorial">
        <span class="lable">Tutorial</span>
    </button>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {

    const archivoInput   = document.getElementById("archivo");
    const loteSelect     = document.getElementById("loteSelect");
    const loaderBar      = document.getElementById("loaderBar");
    const loaderProgress = document.getElementById("loaderProgress");

    const btnImportar = document.getElementById("btnImportar");
    const btnExportar = document.getElementById("btnExportar");
    const btnLimpiar  = document.getElementById("btnLimpiar");
    const btnTutorial = document.getElementById("btnTutorial");

    let cargando = false;

    // ===================================================
    // MODAL TUTORIAL
    // ===================================================
    const tutorialModalEl = document.getElementById('tutorialModal');
    const tutorialBody = document.getElementById('tutorialBody');
    const tutorialAccept = document.getElementById('tutorialAccept');

    if (tutorialModalEl && tutorialBody && tutorialAccept && window.bootstrap) {
        const tutorialModal = new bootstrap.Modal(tutorialModalEl, { backdrop: 'static', keyboard: false });

        // Función para habilitar el botón solo al llegar al final
        function checkScrollEnd() {
            const atBottom = tutorialBody.scrollTop + tutorialBody.clientHeight >= tutorialBody.scrollHeight - 2;
            tutorialAccept.disabled = !atBottom;
        }

        tutorialBody.addEventListener("scroll", checkScrollEnd);

        // Abrir modal al presionar el botón Tutorial
        btnTutorial.addEventListener("click", () => {
            tutorialAccept.disabled = true;
            tutorialBody.scrollTop = 0;
            tutorialModal.show();
            checkScrollEnd();
        });

        // Cerrar modal al aceptar
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
        window.location.href = "{{ route('documentos.index') }}" + "?lote=" + lote;
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
        formData.append("_token", "{{ csrf_token() }}");

        loaderBar.style.display = "block";
        loaderProgress.style.width = "20%";

        fetch("{{ route('documentos.importar') }}", {
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

        window.location.href = "{{ route('documentos.exportar') }}" + "?lote_id=" + lote;
    };

    // ===================================================
    // LIMPIAR VISTA
    // ===================================================
    btnLimpiar.onclick = () => {

        if (cargando) return;

        const hayArchivo = archivoInput.files.length > 0;
        const hayLote = loteSelect.value !== "";
        const hayTabla = document.querySelector("tbody tr td").innerText.trim() !== 
                        "Vista limpia — No hay documentos cargados" &&
                        document.querySelector("tbody tr td").innerText.trim() !== 
                        "No hay documentos en este lote";

        if (!hayArchivo && !hayLote && !hayTabla) {
            alert("⚠️ No hay nada para limpiar.");
            return;
        }

        archivoInput.value = "";
        loteSelect.selectedIndex = 0;

        const tableBody = document.querySelector("tbody");

        tableBody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center p-3 text-muted">
                    Vista limpia — No hay documentos cargados
                </td>
            </tr>
        `;

        window.location.href = "{{ route('documentos.index') }}";
    };

}); // Cierre de DOMContentLoaded
</script>

@endsection