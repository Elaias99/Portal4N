<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">



    @vite([
        'resources/css/landing.css', //->Estilo de página del portal 
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
                <img src="{{ asset('images/imagen1.webp') }}" alt="Recursos Humanos" loading="lazy">
                <div class="text-overlay">Recursos Humanos</div>
            </a>
        </div>


        <div class="image-container">
            <a href="{{ route('login') }}">
                <img src="{{ asset('images/imagen2.webp') }}" alt="Acceso Empleados" loading="lazy">
                <div class="text-overlay">Acceso Empleados</div>
            </a>
        </div>

    </section>

    <footer>©4Nlogistica. Todos los derechos reservados.</footer>

</body>
</html>
