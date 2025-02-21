@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Detalle de la Factura</h1>
    <div class="card">
        <div class="card-header">
            Factura #{{ $factura->id }}
        </div>
        <div class="card-body">
            <p><strong>Proveedor:</strong> {{ $factura->proveedor->razon_social }}</p>
            <p><strong>Empresa:</strong> {{ $factura->empresa->nombre }}</p>
            <p><strong>Glosa:</strong> {{ $factura->glosa }}</p>
            <p><strong>Estado:</strong> {{ $factura->status }}</p>
            <p><strong>Fecha de Creación:</strong> {{ $factura->created_at }}</p>
        </div>
    </div>
    <a href="{{ route('facturas.index') }}" class="btn btn-primary mt-3">Volver al Listado</a>
</div>
@endsection
