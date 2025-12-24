@extends('layouts.app')

@section('content')

<div class="container">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">Correos automáticos</h1>

        <a href="{{ route('admin.automatic_emails.create') }}"
           class="btn btn-primary">
            Nuevo correo automático
        </a>
    </div>

    <div class="email-rules">

        @forelse ($emails as $email)
            <div class="email-rule">

                <div class="email-rule-content">

                    <div class="email-rule-title">
                        {{ $email->nombre }}
                    </div>

                    <div class="email-rule-subject">
                        {{ $email->asunto }}
                    </div>

                    <div class="email-rule-details">
                        <div>
                            <strong>De:</strong>
                            {{ config('mail.from.address') }}
                        </div>

                        <div>
                            <strong>Para:</strong>
                            {{ $email->destinatarios }}
                        </div>
                    </div>




                    <div class="email-rule-meta">

                        {{-- DESCRIPCIÓN HUMANA --}}
                        <span>
                            @php
                                $hora = $email->hora_envio ? substr($email->hora_envio, 0, 5) : 'hora no definida';

                                $diasSemana = [
                                    1 => 'lunes',
                                    2 => 'martes',
                                    3 => 'miércoles',
                                    4 => 'jueves',
                                    5 => 'viernes',
                                    6 => 'sábado',
                                    0 => 'domingo',
                                ];

                                $diasSeleccionados = collect($email->dias_semana ?? [])
                                    ->map(fn($d) => $diasSemana[$d] ?? null)
                                    ->filter()
                                    ->values()
                                    ->implode(', ');
                            @endphp

                            @if ($email->tipo_frecuencia === 'diario')
                                Se envía todos los días a las {{ $hora }}
                            @elseif ($email->tipo_frecuencia === 'semanal')
                                Se envía los {{ $diasSeleccionados }} a las {{ $hora }}
                            @elseif ($email->tipo_frecuencia === 'mensual')
                                Se envía el primer día de cada mes a las {{ $hora }}
                            @endif
                        </span>

                        <span class="dot">·</span>

                        <span class="{{ $email->activo ? 'status-active' : 'status-inactive' }}">
                            {{ $email->activo ? 'Activo' : 'Inactivo' }}
                        </span>

                    </div>






                </div>

                <div class="email-rule-actions">
                    <a href="{{ route('admin.automatic_emails.edit', $email->id) }}">
                        Editar
                    </a>

                    <form method="POST"
                        action="{{ route('admin.automatic_emails.destroy', $email->id) }}"
                        onsubmit="return confirm('¿Eliminar este correo automático?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit">
                            Eliminar
                        </button>
                    </form>
                </div>

            </div>
        @empty
            <div class="email-rules-empty">
                No hay correos automáticos creados.
            </div>
        @endforelse


    </div>

</div>


<style>


    .email-rules {
        background: #ffffff;
        border-radius: 6px;
        border: 1px solid #e5e7eb;
    }

    .email-rule {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        padding: 16px;
        border-bottom: 1px solid #e5e7eb;
    }

    .email-rule:last-child {
        border-bottom: none;
    }

    .email-rule-title {
        font-size: 0.95rem;
        font-weight: 600;
        color: #111827;
    }

    .email-rule-subject {
        font-size: 0.85rem;
        color: #6b7280;
        margin-top: 2px;
    }

    .email-rule-meta {
        font-size: 0.75rem;
        color: #9ca3af;
        margin-top: 6px;
    }

    .email-rule-meta .dot {
        margin: 0 6px;
    }

    .status-active {
        color: #047857;
    }

    .status-inactive {
        color: #6b7280;
    }

    .email-rule-actions {
        display: flex;
        align-items: center;
        gap: 12px;
        white-space: nowrap;
    }

    .email-rule-actions a,
    .email-rule-actions button {
        background: none;
        border: none;
        padding: 0;
        font-size: 0.8rem;
        color: #2563eb;
        cursor: pointer;
    }

    .email-rule-actions button {
        color: #dc2626;
    }

    .email-rules-empty {
        padding: 24px;
        text-align: center;
        color: #6b7280;
        font-size: 0.85rem;
    }


    .email-rule-details {
        margin-top: 8px;
        font-size: 0.8rem;
        color: #374151;
    }

    .email-rule-details div {
        margin-bottom: 2px;
    }

    .email-rule-details strong {
        font-weight: 500;
        color: #111827;
    }

    .email-rule-range {
        margin-top: 6px;
        font-size: 0.75rem;
        color: #9ca3af;
    }






</style>

@endsection
