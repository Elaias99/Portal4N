{{--
    Encabezado de una pantalla del recorrido.

    La regla, acordada con Elías: una pantalla, una pregunta. El título
    es la pregunta y el cuerpo la responde. Si algo no ayuda a responder
    esa pregunta, no va en esta pantalla.

    Cierra en courier.partials.paso-pie.
--}}
<section class="co-paso" aria-labelledby="co-paso-titulo">

    <header class="co-paso-cabeza">
        <p class="co-paso-cuenta">Paso {{ $paso['numero'] }} de {{ $paso['total'] }}</p>
        <h1 class="co-paso-pregunta" id="co-paso-titulo">{{ $paso['pregunta'] }}</h1>
        <p class="co-paso-bajada">{{ $paso['bajada'] }}</p>
    </header>

    <div class="co-paso-cuerpo">
