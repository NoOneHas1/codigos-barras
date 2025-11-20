<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <title>@yield('title', 'Gestor de documentos')</title>

    @vite(['resources/css/app.css'])

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

    <!-- Contenedor de Bootstrap Toasts -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1080">
    @if(session('success'))
        @foreach((array) session('success') as $msg)
            <div class="toast align-items-center text-bg-success border-0 mb-2" role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        {!! $msg !!}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto"
                        data-bs-dismiss="toast"></button>
                </div>
            </div>
        @endforeach
    @endif

    @if(session('error'))
        @foreach((array) session('error') as $msg)
            <div class="toast align-items-center text-bg-danger border-0 mb-2" role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        {!! $msg !!}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto"
                        data-bs-dismiss="toast"></button>
                </div>
            </div>
        @endforeach
    @endif
</div>
<div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom border-3 border-warning">
    <h2>Gestión de Documentos</h2>
    <button id="btnTutorial" class="btn-tutorial shadow-sm ms-auto height-40" style="height: 40px;">
            <span class="lable">Ayuda</span>
        </button>
</div>
    @yield('content')

    
</div>
@vite(['resources/js/app.js'])
</body>
</html>
