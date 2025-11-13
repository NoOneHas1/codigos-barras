<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ver Documento</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
    <h2>{{ $documento->nombre }}</h2>
    <p>
        <a href="{{ asset('storage/' . $documento->archivo) }}" target="_blank">Ver archivo</a>
    </p>
    <p>
        <img src="{{ asset('storage/' . $documento->codigo_gs1) }}" width="250">
    </p>
    <a href="{{ route('documentos.index') }}" class="btn btn-secondary">Volver</a>
</body>
</html>
