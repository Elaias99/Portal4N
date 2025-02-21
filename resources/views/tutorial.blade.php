@extends('layouts.app')

@section('content')

<div class="container">
    <h1 class="text-center my-4">📹 Guía del Sistema paso a paso</h1>

    <div class="accordion" id="videoAccordion">

        <!-- Video 1 -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingOne">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#video1" aria-expanded="true" aria-controls="video1">
                    📌 Cómo acceder al sistema y pantalla de inicio
                </button>
            </h2>
            <div id="video1" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#videoAccordion">
                <div class="accordion-body">
                    <video controls class="w-100">
                        <source src="{{ asset('videos/video1_ingresar.mp4') }}" type="video/mp4">
                        Tu navegador no soporta la reproducción de video.
                    </video>
                </div>
            </div>
        </div>

        <!-- Video 2 -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingTwo">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#video2" aria-expanded="false" aria-controls="video2">
                    📌 Explorando la lista de empleados y su información
                </button>
            </h2>
            <div id="video2" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#videoAccordion">
                <div class="accordion-body">
                    <video controls class="w-100">
                        <source src="{{ asset('videos/video2_listado_empleado.mp4') }}" type="video/mp4">
                        Tu navegador no soporta la reproducción de video.
                    </video>
                </div>
            </div>
        </div>

        <!-- Video 3 -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingThree">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#video3" aria-expanded="false" aria-controls="video3">
                    📌 Registro de nuevos empleados en el sistema
                </button>
            </h2>
            <div id="video3" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#videoAccordion">
                <div class="accordion-body">
                    <video controls class="w-100">
                        <source src="{{ asset('videos/video3_crear_empleado.mp4') }}" type="video/mp4">
                        Tu navegador no soporta la reproducción de video.
                    </video>
                </div>
            </div>
        </div>

        <!-- Video 4 -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingFour">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#video4" aria-expanded="false" aria-controls="video4">
                    📌 Botones de acción
                </button>
            </h2>
            <div id="video4" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#videoAccordion">
                <div class="accordion-body">
                    <video controls class="w-100">
                        <source src="{{ asset('videos/video4_botones_accion.mp4') }}" type="video/mp4">
                        Tu navegador no soporta la reproducción de video.
                    </video>
                </div>
            </div>
        </div>

        <!-- Video 5 -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingFive">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#video5" aria-expanded="false" aria-controls="video5">
                    📌 Barra lateral e información
                </button>
            </h2>
            <div id="video5" class="accordion-collapse collapse" aria-labelledby="headingFive" data-bs-parent="#videoAccordion">
                <div class="accordion-body">
                    <video controls class="w-100">
                        <source src="{{ asset('videos/video5_barra_lateral.mp4') }}" type="video/mp4">
                        Tu navegador no soporta la reproducción de video.
                    </video>
                </div>
            </div>
        </div>

        <!-- Video 6 -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingSix">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#video6" aria-expanded="false" aria-controls="video6">
                    📌 Gestionar las Solicitudes
                </button>
            </h2>
            <div id="video6" class="accordion-collapse collapse" aria-labelledby="headingSix" data-bs-parent="#videoAccordion">
                <div class="accordion-body">
                    <video controls class="w-100">
                        <source src="{{ asset('videos/video6_solicitudes.mp4') }}" type="video/mp4">
                        Tu navegador no soporta la reproducción de video.
                    </video>
                </div>
            </div>
        </div>

        <!-- Video 7 -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingSeven">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#video7" aria-expanded="false" aria-controls="video7">
                    📌 Subida y gestión de documentos en las solicitudes
                </button>
            </h2>
            <div id="video7" class="accordion-collapse collapse" aria-labelledby="headingSeven" data-bs-parent="#videoAccordion">
                <div class="accordion-body">
                    <video controls class="w-100">
                        <source src="{{ asset('videos/video7_archivos_adjuntos.mp4') }}" type="video/mp4">
                        Tu navegador no soporta la reproducción de video.
                    </video>
                </div>
            </div>
        </div>

        <!-- Video 8 -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingEight">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#video8" aria-expanded="false" aria-controls="video8">
                    📌 Centro de Gestión
                </button>
            </h2>
            <div id="video8" class="accordion-collapse collapse" aria-labelledby="headingEight" data-bs-parent="#videoAccordion">
                <div class="accordion-body">
                    <video controls class="w-100">
                        <source src="{{ asset('videos/video8_centro_gestion.mp4') }}" type="video/mp4">
                        Tu navegador no soporta la reproducción de video.
                    </video>
                </div>
            </div>
        </div>

        <!-- Video 9 -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingNine">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#video9" aria-expanded="false" aria-controls="video9">
                    📌 Historial de Vacaciones
                </button>
            </h2>
            <div id="video9" class="accordion-collapse collapse" aria-labelledby="headingNine" data-bs-parent="#videoAccordion">
                <div class="accordion-body">
                    <video controls class="w-100">
                        <source src="{{ asset('videos/video9_historial_vacaciones.mp4') }}" type="video/mp4">
                        Tu navegador no soporta la reproducción de video.
                    </video>
                </div>
            </div>
        </div>

    </div>
</div>

@endsection
