<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title', 'Gestor de documentos')</title>

   {{-- Bootstrap --}}
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

    <style>

        body {
            margin: 0;
            font-family: 'Open Sans', sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            background-color: #f8f9fa; /* color de fondo general */
        }

        /* Logo */ 
        .header-logo {
            width: 100%;
            background: linear-gradient(90deg, #ffeb3b, #fff176);* degradado amarillo */
            padding: 30px 0;
            text-align: start;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .banner{
            
        }

        .header-logo img {
            max-height: 80px;
        }

        /* Barra de carga estilo Google Drive */
        #loaderBar {
            display: none;
            margin-top: 10px;
        }
        .progress {
            height: 10px;
        }

        /* Estilos butones */
        .btn {
            appearance: none;
            background-color: transparent;
            border: 0.125em solid #1A1A1A;
            border-radius: 0.9375em;
            box-sizing: border-box;
            color: #3B3B3B;
            cursor: pointer;
            display: inline-block;
            font-size: 16px;
            font-weight: 600;
            line-height: normal;
            margin: 0;
            min-height: 3.75em;
            min-width: 0;
            outline: none;
            padding: 1em 2.3em;
            text-align: center;
            text-decoration: none;
            transition: all 300ms cubic-bezier(.23, 1, 0.32, 1);
            user-select: none;
            -webkit-user-select: none;
            touch-action: manipulation;
            will-change: transform;
        }

        .btn:disabled {
            pointer-events: none;
        }

        .btn:hover {
            color: #fff;
            background-color: #1A1A1A;
            box-shadow: rgba(0, 0, 0, 0.25) 0 8px 15px;
            transform: translateY(-2px);
        }

        .btn:active {
            box-shadow: none;
            transform: translateY(0);
        }

    </style>
</head>

<body class="bg-light">

{{-- header --}}
<div class="banner"></div>
<div class="header-logo">
    <img src="{{ asset('assets/images/Logo.png') }}" alt="logo">
</div>
<div class="banner"></div>
    



<div class="container p-4" style="max-width: 900px;">

    {{-- MENSAJES --}}
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <h2 class="mb-4 fw-bold">Gestión de Documentos</h2>

    @yield('content')

</body>
</html>