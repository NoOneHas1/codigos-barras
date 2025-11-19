@extends('layouts.app')

@section('title', 'Listado de Documentos')

@section('content')

{{-- =======================
        ALERTAS
======================= --}}
@if (session('success'))
    <script>
        Swal.fire({
            icon: "success",
            title: "Éxito",
            html: {!! json_encode(session('success')) !!},
            confirmButtonColor: "#0d6efd",
        });
    </script>
@endif

@if (session('error'))
    <script>
        Swal.fire({
            icon: "error",
            title: "Error",
            html: {!! json_encode(session('error')) !!},
            confirmButtonColor: "#dc3545",
        });
    </script>
@endif



{{-- =======================
  MODAL EDITAR LOTE
======================= --}}
<div class="modal fade" id="modalEditarLote" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content shadow-sm">
            <form method="POST" action="{{ route('documentos.editarLote') }}">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Renombrar Lote</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <label class="fw-bold">Nombre actual</label>
                    <input type="text" id="lote_old" name="lote_old"
                           class="form-control mb-3" readonly>

                    <label class="fw-bold">Nuevo nombre</label>
                    <input type="text" id="lote_new" name="lote_new"
                           class="form-control" required>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>



{{-- =======================
  MODAL CONFIRMAR ELIMINAR
======================= --}}
<div class="modal fade" id="modalEliminarLote" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">

      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">Eliminar lote</h5>
      </div>

      <div class="modal-body">
        <p>¿Seguro que deseas eliminar el lote <b id="loteAEliminarTexto"></b>?</p>
        <p class="text-danger">Se eliminarán todos los documentos y sus códigos de barras.</p>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>

        <form id="formEliminarLote" method="POST" action="{{ route('documentos.eliminarLote') }}">
            @csrf
            <input type="hidden" name="lote" id="loteAEliminar">
            <button type="submit" class="btn btn-danger">Eliminar</button>
        </form>
      </div>

    </div>
  </div>
</div>

{{-- =======================
   MODAL IMPORTAR ARCHIVO
======================= --}}
<div class="modal fade" id="modalImportarArchivo" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" enctype="multipart/form-data"
              action="{{ route('documentos.importar') }}">
            @csrf

            <div class="modal-header">
                <h5 class="modal-title fw-bold">Importar archivo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                {{-- ARCHIVO --}}
                <label class="fw-bold">Archivo seleccionado</label>
                <input type="text" id="archivoNombreMostrar" class="form-control mb-3" readonly>

                {{-- CAMPO LOTE --}}
                <label class="fw-bold">Nombre del lote</label>
                <input type="text" name="lote" id="loteNameInput" class="form-control" required>

                {{-- INPUT REAL DEL ARCHIVO --}}
                <input type="file" name="archivo" id="archivoRealInput"
                       accept=".xls,.xlsx,.xlsm,.xlsb" hidden required>

            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-primary">Importar</button>
            </div>

        </form>
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

    <select id="loteSelect" class="form-select" style="height: 40px; max-width: 180px;">
        <option value="">Lote</option>
        @foreach ($lotes as $l)
            <option value="{{ $l }}" {{ $l == $lote ? 'selected' : '' }}>
                {{ $l }}
            </option>
        @endforeach
    </select>

    <button id="btnExportar" class="btn btn-success" style="height: 40px;">Exportar</button>
    <button id="btnEliminarLote" class="btn btn-danger">Eliminar lote</button>
    <button id="btnEditarLote" class="btn btn-warning text-white" style="height:40px;">Editar Lote</button>
    <button id="btnLimpiar" class="btn btn-warning">Limpiar</button>
</div>



{{-- =======================
       TABLA
======================= --}}
<div class="card mt-4">
    <div class="card-header">
        Lote {{ $lote }}
    </div>

    <div class="card-body p-0">
        <table class="table table-striped table-hover mb-0 align-middle">
            <thead>
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

@endsection

<script>
    window.appConfig = {
        importarUrl: "{{ route('documentos.importar') }}",
        exportarUrl: "{{ route('documentos.exportar') }}",
        indexUrl: "{{ route('documentos.index') }}",
        csrfToken: "{{ csrf_token() }}"
    };
</script>
