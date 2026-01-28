@extends('layouts.app')

@section('content')
<style>
    .panel-wrapper {
        max-width: 900px;
        margin: 120px auto;
        padding: 0 24px;
    }

    .panel-header {
        margin-bottom: 48px;
    }

    .panel-header h1 {
        margin: 0 0 8px 0;
        font-size: 32px;
        font-weight: 600;
    }

    .panel-header p {
        margin: 0;
        font-size: 16px;
        color: #64748b;
    }

    .panel-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 24px;
    }

    .panel-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 32px;
        text-decoration: none;
        color: inherit;
        transition: box-shadow 0.2s ease, transform 0.2s ease;
    }

    .panel-card:hover {
        box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        transform: translateY(-2px);
    }

    .panel-card h2 {
        margin: 0 0 12px 0;
        font-size: 22px;
        font-weight: 600;
    }

    .panel-card p {
        margin: 0;
        font-size: 15px;
        color: #64748b;
        line-height: 1.5;
    }

    .panel-tag {
        display: inline-block;
        margin-top: 16px;
        font-size: 13px;
        font-weight: 500;
        padding: 6px 12px;
        border-radius: 999px;
        background: #e0e7ff;
        color: #1e40af;
    }
</style>

<div class="panel-wrapper">

    <div class="panel-header">
        <h1>Módulo de Etiquetas Zebra</h1>
        <p>Selecciona el tipo de etiqueta que deseas generar.</p>
    </div>

    <div class="panel-grid">

        <!-- Etiquetas pequeñas -->
        <a href="{{ url('/labels/excel') }}" class="panel-card">
            <h2>Etiquetas 70×30 mm</h2>
            <p>
                Genera etiquetas pequeñas a partir de un archivo Excel,
                ideales para sobres y bultos individuales.
            </p>
            <span class="panel-tag">Formato pequeño</span>
        </a>

        <!-- Etiquetas grandes -->
        <a href="{{ url('/labels/grande') }}" class="panel-card">
            <h2>Etiquetas 10×15 cm</h2>
            <p>
                Genera etiquetas grandes con detalle completo del contenido,
                listado por destinatario y total de revistas.
            </p>
            <span class="panel-tag">Formato grande</span>
        </a>

    </div>

</div>
@endsection
