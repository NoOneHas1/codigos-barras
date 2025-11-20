@extends('layouts.app')

@section('title', 'Listado de Documentos')

@section('content')

{{-- IMPORTAR MODAL --}}
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

                 {{-- NOMBRE OPCIONAL --}}
                <label class="fw-bold mt-2">Nombre del archivo exportado (opcional)</label>
                <input type="text" name="nombre_exportado" id="nombreExportadoInput" class="form-control" placeholder="Ej: DocumentosProcesados">

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

{{-- CONTROLES --}}
<div class="d-flex gap-2 mb-4 align-items-center flex-wrap shadow-sm p-3 bg-white rounded-3">
    <input type="file" id="archivo" class="form-control"
        style="height: 40px; flex: 1; min-width: 200px;"
        accept=".xls,.xlsx,.xlsm,.xlsb,.xlt,.xltm,.xltx">

    <button id="btnImportar" class="btn btn-primary" style="height: 40px;">
        Importar
    </button>

    <form id="formExport" method="GET" action="{{ route('documentos.exportar') }}">
        @csrf
        <button id="btnExportar" type="submit" 
            class="btn btn-success" 
            style="height: 40px;"
            @if(count($documentos) == 0) disabled @endif>
            Exportar
        </button>

    </form>

    <form id="formLimpiar" method="POST" action="{{ route('documentos.limpiar') }}">
        @csrf
        <button id="btnLimpiar" type="submit" class="btn btn-secondary">Limpiar</button>
    </form>
</div>

{{-- TABLA --}}
<div class="card mt-4">
    <div class="card-header">
        <div>
            <img src="{{ Vite::asset('resources/images/icons/caja.svg') }}" alt="box">
            Documentos temporales
        </div>
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
                        <td>{{ $doc['tipo_doc'] }}</td>
                        <td>{{ $doc['numero_doc'] }}</td>
                        <td>{{ $doc['nombre'] }}</td>
                        <td>
                            @if(!empty($doc['barcode_base64']))
                                <img src="data:image/png;base64,{{ $doc['barcode_base64'] }}" 
                                    class="img-fluid shadow-sm rounded" style="height: 40px;">
                            @endif
                        </td>
                        <td>{{ $doc['created_at'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">
                            No hay documentos cargados
                        </td>
                    </tr>
                @endforelse
            </tbody>

        </table>
<div class="card mt-4">
    <div class="card-body d-flex justify-content-between align-items-center flex-wrap">
        
        <div class="text-muted small mb-2">
            Mostrando 
            <strong>{{ $documentos->firstItem() }}</strong> a
            <strong>{{ $documentos->lastItem() }}</strong> de
            <strong>{{ $documentos->total() }}</strong> documentos
        </div>

        <div>
            {{ $documentos->links() }}
        </div>
    </div>
</div>

    </div>
</div>
<script>
    window.appConfig = {
        importarUrl: "{{ route('documentos.importar') }}",
        exportarUrl: "{{ route('documentos.exportar') }}",
        indexUrl: "{{ route('documentos.index') }}",
        csrfToken: "{{ csrf_token() }}"
    };
</script>
@endsection
