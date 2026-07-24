@extends('layouts.app')

@section('content')

@php
    $meses = [
        1  => 'Enero',
        2  => 'Febrero',
        3  => 'Marzo',
        4  => 'Abril',
        5  => 'Mayo',
        6  => 'Junio',
        7  => 'Julio',
        8  => 'Agosto',
        9  => 'Septiembre',
        10 => 'Octubre',
        11 => 'Noviembre',
        12 => 'Diciembre',
    ];

    $nombreMesSeleccionado = $meses[$mes] ?? 'Mes desconocido';

    $anioActual = now()->year;

    $aniosDisponibles = range(
        $anioActual + 1,
        $anioActual - 5
    );
@endphp

<div
    class="container almacenamiento-historial"
    data-almacenamiento-historial
>
    {{-- Mensajes de confirmación --}}
    @if(session('success'))
        <div
            class="alert alert-success alert-dismissible fade show shadow-sm"
            role="alert"
        >
            <strong>Operación completada.</strong>
            {{ session('success') }}

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
                aria-label="Cerrar"
            ></button>
        </div>
    @endif

    {{-- Encabezado principal --}}
    <section class="almacenamiento-historial__header">
        <div class="almacenamiento-historial__header-content">

            <div class="almacenamiento-historial__header-information">

                <div class="almacenamiento-historial__header-symbol">
                    H
                </div>

                <div>
                    <p class="almacenamiento-historial__eyebrow">
                        Registro de operaciones
                    </p>

                    <h1 class="almacenamiento-historial__title">
                        Historial de bodega
                    </h1>

                    <p class="almacenamiento-historial__description">
                        Consulta los productos creados y eliminados durante
                        cada período.
                    </p>
                </div>

            </div>

            <div class="almacenamiento-historial__header-actions">
                <a
                    href="{{ route('almacenamiento.index') }}"
                    class="btn btn-outline-secondary"
                >
                    Volver al inventario
                </a>
            </div>

        </div>
    </section>

    {{-- Filtros por período --}}
    <section class="almacenamiento-historial__filters">

        <div class="almacenamiento-historial__filters-header">
            <div>
                <p class="almacenamiento-historial__filters-eyebrow">
                    Período de consulta
                </p>

                <h2 class="almacenamiento-historial__filters-title">
                    Seleccionar mes
                </h2>

                <p class="almacenamiento-historial__filters-description">
                    Escoge el año y mes del historial que deseas revisar.
                </p>
            </div>
        </div>

        <form
            action="{{ route('almacenamiento.historial.index') }}"
            method="GET"
            class="almacenamiento-historial__filter-form"
        >
            <div class="almacenamiento-historial__filter-field">
                <label
                    for="anio"
                    class="form-label"
                >
                    Año
                </label>

                <select
                    name="anio"
                    id="anio"
                    class="form-select"
                >
                    @foreach($aniosDisponibles as $anioDisponible)
                        <option
                            value="{{ $anioDisponible }}"
                            @selected((int) $anio === $anioDisponible)
                        >
                            {{ $anioDisponible }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="almacenamiento-historial__filter-field">
                <label
                    for="mes"
                    class="form-label"
                >
                    Mes
                </label>

                <select
                    name="mes"
                    id="mes"
                    class="form-select"
                >
                    @foreach($meses as $numeroMes => $nombreMes)
                        <option
                            value="{{ $numeroMes }}"
                            @selected((int) $mes === $numeroMes)
                        >
                            {{ $nombreMes }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="almacenamiento-historial__filter-actions">
                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Consultar historial
                </button>

                <a
                    href="{{ route('almacenamiento.historial.index') }}"
                    class="btn btn-outline-secondary"
                >
                    Período actual
                </a>
            </div>
        </form>

    </section>

    {{-- Panel principal --}}
    <section class="almacenamiento-historial__panel">

        <header class="almacenamiento-historial__panel-header">

            <div>
                <p class="almacenamiento-historial__panel-eyebrow">
                    Movimientos registrados
                </p>

                <h2 class="almacenamiento-historial__panel-title">
                    {{ $nombreMesSeleccionado }} de {{ $anio }}
                </h2>

                <p class="almacenamiento-historial__panel-description">
                    Se encontraron
                    <strong>
                        {{ number_format($historial->total(), 0, ',', '.') }}
                    </strong>

                    {{ $historial->total() === 1
                        ? 'movimiento'
                        : 'movimientos'
                    }}
                    durante el período seleccionado.
                </p>
            </div>

            <div class="almacenamiento-historial__period">
                <span class="almacenamiento-historial__period-label">
                    Período
                </span>

                <strong class="almacenamiento-historial__period-value">
                    {{ str_pad((string) $mes, 2, '0', STR_PAD_LEFT) }}/{{ $anio }}
                </strong>
            </div>

        </header>

        @if($historial->isEmpty())

            {{-- Estado sin movimientos --}}
            <div class="almacenamiento-historial__empty">

                <div class="almacenamiento-historial__empty-symbol">
                    H
                </div>

                <h3 class="almacenamiento-historial__empty-title">
                    No existen movimientos en este período
                </h3>

                <p class="almacenamiento-historial__empty-description">
                    No se registraron productos creados o eliminados durante
                    {{ mb_strtolower($nombreMesSeleccionado) }}
                    de {{ $anio }}.
                </p>

                <a
                    href="{{ route('almacenamiento.index') }}"
                    class="btn btn-primary"
                >
                    Ir al inventario
                </a>

            </div>

        @else

            {{-- Tabla del historial --}}
            <div class="table-responsive">
                <table class="table almacenamiento-historial__table align-middle">

                    <thead>
                        <tr>
                            <th scope="col">
                                Fecha y hora
                            </th>

                            <th scope="col">
                                Producto
                            </th>

                            <th scope="col">
                                ID original
                            </th>

                            <th
                                scope="col"
                                class="text-center"
                            >
                                Acción
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($historial as $registro)
                            @php
                                $accion = mb_strtoupper(
                                    trim($registro->accion)
                                );

                                $esCreacion = $accion === 'CREADO';
                            @endphp

                            <tr>
                                {{-- Fecha --}}
                                <td>
                                    <div class="almacenamiento-historial__date">
                                        <strong>
                                            {{ $registro->created_at->format('d-m-Y') }}
                                        </strong>

                                        <span>
                                            {{ $registro->created_at->format('H:i') }}
                                        </span>
                                    </div>
                                </td>

                                {{-- Producto --}}
                                <td>
                                    <div class="almacenamiento-historial__product">

                                        <div class="almacenamiento-historial__product-symbol">
                                            {{ mb_strtoupper(
                                                mb_substr(
                                                    $registro->nombre_producto,
                                                    0,
                                                    1
                                                )
                                            ) }}
                                        </div>

                                        <div>
                                            <strong class="almacenamiento-historial__product-name">
                                                {{ $registro->nombre_producto }}
                                            </strong>

                                            <span class="almacenamiento-historial__product-detail">
                                                Registro histórico
                                            </span>
                                        </div>

                                    </div>
                                </td>

                                {{-- ID original --}}
                                <td>
                                    <span class="almacenamiento-historial__reference">
                                        #{{ $registro->almacenamiento_bodega_id }}
                                    </span>
                                </td>

                                {{-- Acción --}}
                                <td class="text-center">
                                    <span
                                        class="almacenamiento-historial__status
                                            {{ $esCreacion
                                                ? 'almacenamiento-historial__status--created'
                                                : 'almacenamiento-historial__status--deleted'
                                            }}"
                                    >
                                        {{ $accion }}
                                    </span>

                                    <small class="almacenamiento-historial__status-detail">
                                        {{ $esCreacion
                                            ? 'Producto agregado'
                                            : 'Producto eliminado'
                                        }}
                                    </small>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>

                </table>
            </div>

            {{-- Paginación --}}
            @if($historial->hasPages())
                <footer class="almacenamiento-historial__pagination">
                    <div class="almacenamiento-historial__pagination-detail">
                        Mostrando registros
                        <strong>{{ $historial->firstItem() }}</strong>
                        a
                        <strong>{{ $historial->lastItem() }}</strong>
                        de
                        <strong>{{ $historial->total() }}</strong>.
                    </div>

                    <div>
                        {{ $historial->links() }}
                    </div>
                </footer>
            @endif

        @endif

    </section>

</div>

@endsection