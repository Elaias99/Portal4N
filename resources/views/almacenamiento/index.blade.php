@extends('layouts.app')

@section('content')

@php
    $totalProductos = $almacenamiento->count();

    $totalUnidades = $almacenamiento->sum(function ($producto) {
        return (int) $producto->cantidad;
    });

    $costoInformado = $almacenamiento->sum(function ($producto) {
        return (float) $producto->precio;
    });
@endphp

<div
    class="container almacenamiento-index"
    data-almacenamiento-index
>
    {{-- Mensaje de operación exitosa --}}
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

    {{-- Encabezado del módulo --}}
    <section class="almacenamiento-header">
        <div class="almacenamiento-header__content">
            <div class="almacenamiento-header__information">
                <div class="almacenamiento-header__symbol">
                    <span>B</span>
                </div>

                <div>
                    <p class="almacenamiento-header__eyebrow">
                        Control de existencias
                    </p>

                    <h1 class="almacenamiento-header__title">
                        Inventario de bodega
                    </h1>

                    <p class="almacenamiento-header__description">
                        Consulta y administra los productos almacenados
                        actualmente en la bodega.
                    </p>
                </div>
            </div>

            <div class="almacenamiento-header__actions">
                <a
                    href="{{ route('equipos.index') }}"
                    class="btn btn-outline-secondary"
                >
                    Ver equipos
                </a>

                <a
                    href="{{ route('almacenamiento.historial.index') }}"
                    class="btn btn-outline-info"
                >
                    Ver historial
                </a>

                <a
                    href="{{ route('almacenamiento.create') }}"
                    class="btn btn-primary"
                >
                    Agregar producto
                </a>
            </div>
        </div>
    </section>

    {{-- Resumen general --}}
    <section
        class="almacenamiento-summary"
        aria-label="Resumen del inventario"
    >
        <article class="almacenamiento-summary__item">
            <div class="almacenamiento-summary__icon">
                P
            </div>

            <div>
                <span class="almacenamiento-summary__label">
                    Productos registrados
                </span>

                <strong class="almacenamiento-summary__value">
                    {{ number_format($totalProductos, 0, ',', '.') }}
                </strong>

                <span class="almacenamiento-summary__detail">
                    Productos diferentes
                </span>
            </div>
        </article>

        <article class="almacenamiento-summary__item">
            <div class="almacenamiento-summary__icon">
                U
            </div>

            <div>
                <span class="almacenamiento-summary__label">
                    Unidades disponibles
                </span>

                <strong class="almacenamiento-summary__value">
                    {{ number_format($totalUnidades, 0, ',', '.') }}
                </strong>

                <span class="almacenamiento-summary__detail">
                    Cantidad total registrada
                </span>
            </div>
        </article>

        <article class="almacenamiento-summary__item">
            <div class="almacenamiento-summary__icon">
                $
            </div>

            <div>
                <span class="almacenamiento-summary__label">
                    Costo informado
                </span>

                <strong class="almacenamiento-summary__value">
                    ${{ number_format($costoInformado, 0, ',', '.') }}
                </strong>

                <span class="almacenamiento-summary__detail">
                    Suma de valores ingresados
                </span>
            </div>
        </article>
    </section>

    {{-- Contenedor principal del listado --}}
    <section class="almacenamiento-panel">
        <header class="almacenamiento-panel__header">
            <div>
                <p class="almacenamiento-panel__eyebrow">
                    Existencias actuales
                </p>

                <h2 class="almacenamiento-panel__title">
                    Productos almacenados
                </h2>

                <p class="almacenamiento-panel__description">
                    Revisa la cantidad disponible y la información registrada
                    para cada producto.
                </p>
            </div>

            @if($almacenamiento->isNotEmpty())
                <div class="almacenamiento-search">
                    <label
                        for="buscarProducto"
                        class="visually-hidden"
                    >
                        Buscar producto
                    </label>

                    <input
                        type="search"
                        id="buscarProducto"
                        class="form-control"
                        placeholder="Buscar por nombre o descripción..."
                        autocomplete="off"
                    >

                    <button
                        type="button"
                        id="limpiarBusqueda"
                        class="btn btn-outline-secondary"
                    >
                        Limpiar
                    </button>
                </div>
            @endif
        </header>

        @if($almacenamiento->isEmpty())
            {{-- Estado sin productos --}}
            <div class="almacenamiento-empty">
                <div class="almacenamiento-empty__symbol">
                    B
                </div>

                <h3 class="almacenamiento-empty__title">
                    No existen productos registrados
                </h3>

                <p class="almacenamiento-empty__description">
                    Agrega el primer producto para comenzar a registrar
                    las existencias disponibles en la bodega.
                </p>

                <a
                    href="{{ route('almacenamiento.create') }}"
                    class="btn btn-primary"
                >
                    Agregar primer producto
                </a>
            </div>
        @else
            {{-- Tabla de productos --}}
            <div class="table-responsive">
                <table class="table almacenamiento-table align-middle">
                    <thead>
                        <tr>
                            <th scope="col">
                                Producto
                            </th>

                            <th scope="col">
                                Costo informado
                            </th>

                            <th
                                scope="col"
                                class="text-center"
                            >
                                Existencias
                            </th>

                            <th scope="col">
                                Descripción
                            </th>

                            <th
                                scope="col"
                                class="text-end"
                            >
                                Acciones
                            </th>
                        </tr>
                    </thead>

                    <tbody id="tablaProductos">
                        @foreach($almacenamiento as $producto)
                            @php
                                $cantidad = (int) $producto->cantidad;

                                $contenidoBusqueda = mb_strtolower(
                                    trim(
                                        $producto->Nombre . ' ' .
                                        ($producto->descripcion ?? '')
                                    )
                                );
                            @endphp

                            <tr
                                data-product-row
                                data-search="{{ $contenidoBusqueda }}"
                            >
                                {{-- Información principal --}}
                                <td>
                                    <div class="almacenamiento-product">
                                        <div class="almacenamiento-product__symbol">
                                            {{ mb_strtoupper(
                                                mb_substr($producto->Nombre, 0, 1)
                                            ) }}
                                        </div>

                                        <div>
                                            <strong class="almacenamiento-product__name">
                                                {{ $producto->Nombre }}
                                            </strong>

                                            <span class="almacenamiento-product__reference">
                                                Registro #{{ $producto->id }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                {{-- Costo --}}
                                <td>
                                    <span class="almacenamiento-price">
                                        ${{ number_format(
                                            (float) $producto->precio,
                                            0,
                                            ',',
                                            '.'
                                        ) }}
                                    </span>

                                    <small class="almacenamiento-price__detail">
                                        Valor ingresado
                                    </small>
                                </td>

                                {{-- Cantidad --}}
                                <td class="text-center">
                                    <span
                                        class="almacenamiento-stock
                                            {{ $cantidad > 0
                                                ? 'almacenamiento-stock--available'
                                                : 'almacenamiento-stock--empty'
                                            }}"
                                    >
                                        {{ number_format(
                                            $cantidad,
                                            0,
                                            ',',
                                            '.'
                                        ) }}
                                    </span>

                                    <small class="almacenamiento-stock__status">
                                        {{ $cantidad > 0
                                            ? 'Disponible'
                                            : 'Sin existencias'
                                        }}
                                    </small>
                                </td>

                                {{-- Descripción --}}
                                <td>
                                    <p class="almacenamiento-description">
                                        {{ $producto->descripcion
                                            ?: 'Sin descripción registrada.'
                                        }}
                                    </p>
                                </td>

                                {{-- Acciones --}}
                                <td>
                                    <div class="almacenamiento-actions">
                                        <a
                                            href="{{ route(
                                                'almacenamiento.show',
                                                $producto
                                            ) }}"
                                            class="btn btn-sm btn-outline-info"
                                        >
                                            Ver
                                        </a>

                                        <a
                                            href="{{ route(
                                                'almacenamiento.edit',
                                                $producto
                                            ) }}"
                                            class="btn btn-sm btn-outline-secondary"
                                        >
                                            Editar
                                        </a>

                                        <form
                                            action="{{ route(
                                                'almacenamiento.destroy',
                                                $producto
                                            ) }}"
                                            method="POST"
                                            class="almacenamiento-delete-form"
                                            data-product-name="{{ $producto->Nombre }}"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-outline-danger"
                                            >
                                                Eliminar
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Se controla posteriormente mediante JavaScript --}}
            <div
                id="sinResultados"
                class="almacenamiento-no-results d-none"
            >
                <div class="almacenamiento-no-results__symbol">
                    ?
                </div>

                <p class="almacenamiento-no-results__title">
                    No se encontraron productos
                </p>

                <p class="almacenamiento-no-results__description">
                    Intenta utilizar otro nombre o término de búsqueda.
                </p>
            </div>
        @endif
    </section>
</div>

@endsection