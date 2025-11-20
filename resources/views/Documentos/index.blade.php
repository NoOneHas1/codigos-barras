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


                {{-- INPUT REAL DEL ARCHIVO --}}
                <input type="file" name="archivo" id="archivoRealInput"
                       accept=".xls,.xlsx,.xlsm,.xlsb" hidden required>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Importar</button>
            </div>

        </form>
    </div>
</div>

<!-- MODAL CONFIRMAR LIMPIAR -->
<div class="modal fade" id="modalConfirmarLimpiar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="bi bi-exclamation-triangle-fill"></i> Confirmar limpieza
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body fs-5">
                ¿Seguro que deseas <strong>eliminar todos los documentos cargados</strong>?  
                <br>
                Esta acción no se puede deshacer.
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                 <form id="formLimpiar" method="POST" action="{{ route('documentos.limpiar') }}">
                    @csrf
                <button id="btnLimpiar" type="submit" class="btn btn-danger"  
                    @if(count($documentos) == 0) disabled @endif>
                    Confirmar
                </button>
                </form>
            </div>

        </div>
    </div>
</div>

{{-- Tutorial Modal --}}
<div class="modal fade" id="modalTutorial" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tutorial</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6>Paso a paso para importar archivos Excel</h6>
                <ol>
                    <li>Selecciona un archivo válido (.xlsx, .xls, .csv, .xlsm).</li>
                    <li>Opcionalmente, puedes asignar un <strong>nombre de archivo exportado</strong> en el campo correspondiente.</li>
                    <li>Asegúrate de que tu archivo tenga al menos una fila de datos.</li>
                </ol>

                <h6 class="mt-3">Reglas y condiciones:</h6>
                <ul class="list">
                    <li><strong>IMPORTANTE</strong></li>
                    <li>Las columnas del excel (encabezados) deben tener alguno de los siguientes nombres (sin importar mayúsculas o minúsculas):</li>
                    <ul>
                        <li><strong>Tipo de documento:</strong> {{ implode(', ', ['tipodoc','tipodocumento','tipo','tipodoc','tipodocument']) }}</li>
                        <li><strong>Número de documento:</strong> {{ implode(', ', ['numerodoc','numerodocumento','numero','documento','dni','docnumber','number']) }}</li>
                        <li><strong>Nombre:</strong> {{ implode(', ', ['nombre','nombres','name','fullname','nombrecompleto']) }}</li><br>
                </ul>
                    <li>Las filas completamente vacías se ignoran automáticamente.</li><br>
                    <li>Si el <strong>número de documento</strong> está vacío, esa fila se marcará como error y no se guardará.</li><br>
                    <li>Los códigos de barras se generan automáticamente en memoria y se muestran en la tabla.</li><br>
                    <li>Los documentos nuevos se marcarán en verde para identificar los registros recientemente importados.</li><br>
                </ul>

                <h6 class="mt-3">Notas adicionales:</h6>
                <ul>
                    <li>Los duplicados no se resaltan en verde, pero se pueden importar si así lo deseas.</li>
                    <li>Para ver los documentos recién agregados, busca por el número o usa la paginación. El resaltado verde dura hasta que se hace una nueva importación.</li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>


{{-- CONTROLES --}}
<div class="d-flex gap-2 mb-4 align-items-center flex-wrap shadow-sm p-3 bg-white rounded-3">
        <p class="m-0">¿No tienes el formato correcto? <strong>Descárgalo aquí:</strong></p>  <a href="{{ asset('storage/formato/Formato_Ejemplo.xlsx') }}" style="height: 40px" class="btn btn-outline-primary"download>Descargar</a>
</div>


<div class="d-flex gap-2 mb-4 align-items-center flex-wrap shadow-sm p-3 bg-white rounded-3">
    <input type="file" id="archivo" class="form-control"
        style="height: 40px; flex: 1; min-width: 200px;"
        accept=".xls,.xlsx,.xlsm,.xlsb,.xlt,.xltm,.xltx">

        <div class="groupButtons d-flex gap-2">
    <button id="btnImportar" class="btn" style="height: 40px; background-color: #1b5da4; color: white;" type="button">
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
</div>
    
</div>

{{-- TABLA --}}
<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <img src="{{ asset('storage/images/icons/caja.svg') }}" alt="delete" style="margin-right: 10px;">
            Documentos temporales
        </div>
        <div>
            <button id="confirmarLimpiar" type="button" class="btn btn-danger"  @if(count($documentos) == 0) disabled @endif><img src="{{ asset('storage/images/icons/trash-can-solid-full.svg') }}" alt="box"></button>
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
                <tr @if(!empty($doc['nuevo'])) class="table-warning" @endif>
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






<!-- Overlay de carga -->
<div id="overlayLoader" class="d-none">
    <div class="overlay-content">
        <div class="spinner-border text-primary" role="status">
        </div>
        <p>Espera un momento...</p>
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
