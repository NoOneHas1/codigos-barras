<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <title>@yield('title', 'Gestor de documentos')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])


    {{-- Fuente profesional --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">

   

</head>

<body>

{{-- HEADER --}}
<div class="header-logo">
    <img src="{{ Vite::asset('resources/images/Logo.png') }}">
    <h1>Generador de Codigos de barras</h1>
</div>

{{-- CONTENT --}}
<div class="container py-5" style="max-width: 900px;">

    {{-- Alerts --}}
    @if(session('success'))
        <div class="alert alert-success shadow-sm">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger shadow-sm">{{ session('error') }}</div>
    @endif

    <h2 class="mb-4 pb-2 border-bottom border-3 border-warning">Gestión de Documentos</h2>

    @yield('content')

</div>


</body>
</html>
