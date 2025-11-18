@extends('layouts.app')

@section('title', 'Listado de Documentos')

@section('content')

<!-- Modal Tutorial -->
<div class="modal fade" id="tutorialModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content shadow-lg">
      <div class="modal-header">
        <h5 class="modal-title fw-bold">¿Cómo generar códigos de barras?</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body" id="tutorialBody" style="max-height: 50vh; overflow-y: auto;">
        <p><strong>Paso 1:</strong> Selecciona el archivo Excel que quieres importar.</p>
        <p><strong>Paso 2:</strong> Haz clic en <em>Importar</em> y espera a que termine el proceso.</p>
        <p><strong>Paso 3:</strong> Los datos aparecerán en pantalla según el lote seleccionado.</p>
        <p><strong>Paso 4:</strong> Usa <em>Exportar</em> para bajar el lote seleccionado.</p>
        <p><strong>Paso 5:</strong> Se descargará un Excel con los códigos generados.</p>
        <hr>
      </div>

      <div class="modal-footer">
        <button type="button" id="tutorialAccept" class="btn btn-primary" disabled>Aceptar</button>
      </div>
    </div>
  </div>
</div>



<!-- Modal Editar Nombre Lote -->
<div class="modal fade" id="editLoteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold">Editar nombre del lote</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label for="nuevoNombreLote" class="form-label">Nuevo nombre:</label>
          <input type="text" class="form-control" id="nuevoNombreLote" placeholder="Ej: Documentos profesores">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btnGuardarNombreLote">Guardar</button>
      </div>
    </div>
  </div>
</div>


{{-- =======================
          FORM TOOLS
======================= --}}
<div class="d-flex gap-2 mb-4 align-items-center flex-wrap shadow-sm p-3 bg-white rounded-3">

    <input type="file" id="archivo" class="form-control"
        style="height: 40px; flex: 1; min-width: 200px;"
        accept=".xls,.xlsx,.xlsm,.xlsb,.xlt,.xltm,.xltx">

    <button id="btnImportar" class="btn btn-primary" style="height: 40px;">
        Importar
    </button>

    <select id="loteSelect" class="form-select" style="height: 40px; max-width: 150px;">
        <option value="" disabled>Lote</option>
         @foreach ($lotes as $l)
        @php
             $nombreLote = \App\Models\Documento::where('lote_id', $l)->value('nombre_lote') ?? "Lote {$l}";
        @endphp
        <option value="{{ $l }}" {{ $l == $lote ? 'selected' : '' }}>
            {{ $nombreLote }}
        </option>
    @endforeach
    </select>

    <button id="btnExportar" class="btn btn-success" style="height: 40px;">
        Exportar
    </button>

    

</div>


{{-- =======================
       PROGRESS BAR
======================= --}}
<div id="loaderBar" class="mb-3">
    <div class="progress mb-1">
        <div class="progress-bar progress-bar-striped progress-bar-animated"
             id="loaderProgress" style="width:0%"></div>
    </div>
    <small class="text-muted">Procesando archivo…</small>
</div>


{{-- =======================
          TABLE
======================= --}}
<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
       <span id="nombreLote">
            {{ $nombreLote ?? ($loteNombreBD ?? "Sin nombre") }}
        </span>
       <div>
            @if($lote)
            <button id="btnEditarLote" class="btn btn-secondary flex-shrink-0" style="height: 40px;"
            data-bs-toggle="modal" data-bs-target="#editLoteModal">
                 Editar nombre
            </button>
            @endif
            <button id="btnLimpiar" class="btn btn-secondary" style="height: 40px;">
                <img src="{{ Vite::asset('resources/images/icons/trash-can-solid-full.svg') }}" alt="trash-can">
            </button>
        </div>
    </div>

    <div class="card-body p-0">
        <table class="table table-striped table-hover mb-0 align-middle">
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
                             class="img-fluid shadow-sm rounded"
                             style="height: 40px;">
                    </td>
                    <td>{{ $doc->created_at->format('Y-m-d H:i') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center py-4 text-muted">
                        No hay documentos en este lote
                    </td>
                </tr>
            @endforelse
            </tbody>

        </table>
    </div>
</div>


{{-- Tutorial button --}}
<div class="text-center mt-4">
    <p class="text-muted">¿Necesitas ayuda?</p>
    <button id="btnTutorial" class="btn-tutorial shadow-sm">
        <span class="lable">Tutorial</span>
    </button>
</div>

<script>
    window.appConfig = {
        importarUrl: "{{ route('documentos.importar') }}",
        exportarUrl: "{{ route('documentos.exportar') }}",
        indexUrl: "{{ route('documentos.index') }}",
        csrfToken: "{{ csrf_token() }}",
        actualizarNombreUrl: "{{ route('documentos.actualizarNombre') }}",
        loteId: {{ $lote ?? 'null' }}
    };
</script>


@endsection