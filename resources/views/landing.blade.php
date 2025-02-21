<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    {{-- <link rel="stylesheet" href="{{ asset('css/loader.css') }}">
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}"> --}}

    @vite([

         //-> Camión de carga
        'resources/css/landing.css', //->Estilo de página del portal 
        // otros archivos CSS o JS que necesites
    ])

   
    <title>PORTAL CENTRAL 4N</title>
</head>
<body>

    <header>
        <img src="{{ asset('images/logo.png') }}" alt="Logo de la Empresa"> <!-- Ruta al logo -->
        <h1>PORTAL CENTRAL 4N</h1>
    </header>

    <section>

        <div class="image-container">
            <a href="{{ route('login') }}">
                <img src="{{ asset('images/imagen1.jpg') }}" alt="Recursos Humanos">
                <div class="text-overlay">Recursos Humanos</div>
            </a>
        </div>


        <div class="image-container">
            <a href="{{ route('login') }}">
                <img src="{{ asset('images/imagen2.png') }}" alt="Acceso Empleados">
                <div class="text-overlay">Acceso Empleados</div>
            </a>
        </div>


        <div class="image-container">
            <a href="{{ route('under.construction') }}">
                <img src="{{ asset('images/imagen3.jpg') }}" alt="Logística">
                <div class="text-overlay">Logística</div>
            </a>
        </div>


    </section>

    <footer>© 2024 4Nlogistica. Todos los derechos reservados.</footer>

</body>
</html>
