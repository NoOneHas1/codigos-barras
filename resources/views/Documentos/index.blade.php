@extends('layouts.app')

@section('title', 'Listado de Documentos')

@section('content')


    {{-- ======================================
         BOTONES Y FORMULARIOS
    ======================================= --}}
    <div class="d-flex gap-3 mb-4 align-items-end flex-wrap">

        {{-- INPUT ARCHIVO --}}
        <input type="file" id="archivo" class="form-control"
            accept=".xls,.xlsx,.xlsm,.xlsb,.xlt,.xltm,.xltx,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet">


        {{-- BOTÓN IMPORTAR --}}
        <button id="btnImportar" class="btn btn-primary">
            Importar
        </button>

        {{-- SELECT DE LOTES --}}
        <select id="loteSelect" class="form-select">
            <option value="" selected disabled>Seleccionar lote</option>

            @foreach ($lotes as $l)
                <option value="{{ $l }}" {{ $l == $lote ? 'selected' : '' }}>
                    Lote {{ $l }}
                </option>
            @endforeach
        </select>
        {{-- EXPORTAR --}}
        <button id="btnExportar" class="btn btn-success">
            Exportar lote
        </button>

        {{-- LIMPIAR --}}
        <button id="btnLimpiar" class="btn btn-secondary">
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
        <div class="card-header fw-bold">Documentos del Lote {{ $lote }}</div>

        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead class="table-dark">
                <tr>
                    <th>ID</th>
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
                        <td>{{ $doc->id }}</td>
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
                        <td colspan="6" class="text-center p-3">
                            No hay documentos en este lote
                        </td>
                    </tr>
                @endforelse
                </tbody>

            </table>
        </div>
    </div>

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

        let cargando = false;

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

            // ✅ Validar que haya archivo seleccionado
            if (!archivoInput.files.length) {
                alert("⚠️ Selecciona un archivo Excel antes de importar.");
                return;
            }

            // ✅ Validar tipo válido de Excel
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

            // 🔒 Bloquear toda la interfaz
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

                // URL con ?lote=X generada en el backend
                const redirectURL = response.url;

                loaderProgress.style.width = "100%";

                setTimeout(() => {
                    window.location.href = redirectURL;
                }, 400);
            })
            .catch(() => {
                alert("❌ Error al procesar archivo.");

                cargando = false;

                // 🔓 Restaurar botones
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

            // Validar si hay contenido
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
                    <td colspan="6" class="text-center p-3 text-muted">
                        Vista limpia — No hay documentos cargados
                    </td>
                </tr>
            `;

            window.location.href = "{{ route('documentos.index') }}";
        };
    });
    </script>
 @endsection