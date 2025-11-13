<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Subir documento</title>
</head>
<body>
    <h1>Subir documento</h1>

    @if(session('success'))
        <p style="color: green;">{{ session('success') }}</p>
    @endif

    <form action="{{ url('/documentos') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <label for="archivo">Seleccionar archivo:</label>
        <input type="file" name="archivo" id="archivo" required>

        <br><br>

        <button type="submit">Subir</button>
    </form>
</body>
</html>
