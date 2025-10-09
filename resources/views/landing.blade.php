<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">



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


        <div class="image-container">
            <a href="{{ route('under.construction') }}">
                <img src="{{ asset('images/imagen3.webp') }}" alt="Logística" loading="lazy">
                <div class="text-overlay">Logística</div>
            </a>
        </div>


    </section>

    <footer>©4Nlogistica. Todos los derechos reservados.</footer>


    <!-- Botón de acceso a manifiestos -->
    <a href="{{ route('manifiesto.index') }}"
    title="Ir a herramientas"
    style="
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 9999;
        font-size: 22px;
        background-color: rgba(0, 123, 255, 0.3); /* color azul más translúcido */
        color: rgba(255, 255, 255, 0.6);           /* icono blanco suave */
        border-radius: 50%;
        width: 45px;
        height: 45px;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        box-shadow: none;
        transition: background-color 0.3s, color 0.3s;
    "
    onmouseover="this.style.backgroundColor='rgba(0, 123, 255, 0.6)'; this.style.color='white';"
    onmouseout="this.style.backgroundColor='rgba(0, 123, 255, 0.3)'; this.style.color='rgba(255,255,255,0.6)';"
    >
    <i class="fas fa-cog"></i>
    </a>






</body>
</html>
