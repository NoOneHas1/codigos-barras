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
            align-items: center;
            background-color: #fff;
        }

         /* Logo */ 
        .header-logo {
            display: flex;
            width: 100%;
            background-color: #0f3b53;
            padding: 30px 0;
            text-align: start;
            justify-content: space-between;
            align-items: center;
        }

        .header-logo img {
            max-height: 80px;
            margin-left: 80px;
            margin-right: 0;
        }

        .header-logo h1 {
            color: #ffffff;
            font-size: 24px;
            margin: 0;
            margin-right: 80px;
        }

        /* Estilos tabla */
        .card-header{        
            display: flex;    
            align-items: center;
            justify-content: center;
            color: #fff;
            background-color: #0f3b53;
            font-size: 20px;
        }

        .table-header-custom th{
            background-color: #f7c221;
            color: #1a1a1a;
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
            background-color: #0f3b53;
            border-radius: 0.9375em;
            box-sizing: border-box;
            color: #fff;
            cursor: pointer;
            display: inline-block;
            font-size: 15px;
            font-weight: 600;
            line-height: normal;
            margin: 0;
            min-height: 3em;
            min-width: 0;
            outline: none;
            padding: 0.5em 1.3em;
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
            color: #1a1a1a;
            background-color: #f7c221;
            transform: translateY(-2px);
        }

        .btn:active {
            box-shadow: none;
            transform: translateY(0);
        }

        /*Boton tutorial*/
        .btn-tutorial {
            padding: 6px 12px;
            gap: 8px;
            height: 36px;
            width: 120px;
            border: none;
            background: #0f3b53;
            border-radius: 20px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .lable {
            line-height: 20px;
            font-size: 17px;
            color: #ffffff;
            font-family: sans-serif;
            letter-spacing: 1px;
            }

        .btn-tutorial:hover {
            background: #f7c221;
        }

        .btn-tutorial:hover .lable {
            color: #1a1a1a;
        }

    </style>
</head>

<body class="bg-light">

{{-- header --}}
<div class="header-logo">
    <img src="{{ asset('assets/images/Logo.png') }}" alt="logo">
    <h1>Generador de Codigos de barras</h1>
</div>

    



<div class="container p-4 " style="max-width: 900px; margin-top:5%;">

    {{-- MENSAJES --}}
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <h2 class="mb-4 fw-bold pb-2 border-bottom border-3 border-warning">Gestión de Documentos</h2>

    @yield('content')

    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>