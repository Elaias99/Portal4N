@extends('layouts.app')

@section('content')
<div class="container">
    <h2 class="mb-4">Crear Nueva Cobranza</h2>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('cobranzas.store') }}" method="POST">
                @include('cobranzas.form', ['btnText' => 'Guardar'])
            </form>
        </div>
    </div>
</div>
@endsection
