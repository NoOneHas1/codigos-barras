<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestor de Documentos GS1-128</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <h2 class="mb-4 text-center text-primary">📂 Gestor de Documentos GS1-128</h2>

    @if(session('success'))
        <div class="alert alert-success text-center">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('documentos.importar') }}" method="POST" enctype="multipart/form-data" class="mb-4 p-3 border rounded bg-white shadow-sm">
        @csrf
        <div class="row g-2 align-items-center">
            <div class="col-md-6">
                <input type="file" name="archivo" class="form-control" required>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">📤 Importar Excel</button>
            </div>
            <div class="col-md-3">
                <a href="{{ route('documentos.exportar') }}" class="btn btn-success w-100">📥 Exportar Excel</a>
            </div>
        </div>
    </form>

    <div class="card shadow-sm">
        <div class="card-body">
            <table class="table table-bordered table-hover align-middle text-center">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Tipo Documento</th>
                        <th>Número</th>
                        <th>Nombre</th>
                        <th>Código GS1-128</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($documentos as $doc)
                        <tr>
                            <td>{{ $doc->id }}</td>
                            <td>{{ $doc->tipo_doc }}</td>
                            <td>{{ $doc->numero_doc }}</td>
                            <td>{{ $doc->nombre }}</td>
                            <td>
                                @if(Storage::disk('public')->exists($doc->codigo_path))
                                    <img src="{{ asset('storage/'.$doc->codigo_path) }}" alt="Código de barras" style="height:40px;">
                                @else
                                    <span class="text-danger">No disponible</span>
                                @endif
                            </td>
                            <td>{{ $doc->created_at->format('Y-m-d H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if($documentos->isEmpty())
                <p class="text-center text-muted mt-3">No hay documentos registrados.</p>
            @endif
        </div>
    </div>
</div>

</body>
</html>

