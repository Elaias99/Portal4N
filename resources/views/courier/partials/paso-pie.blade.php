{{-- Cierra courier.partials.paso-cabeza: un solo botón para seguir. --}}
    </div>

    <footer class="co-paso-pie">
        <div>
            @if($paso['anterior'])
                <a href="{{ route('courier.paso', ['paso' => $paso['anterior'], 'periodo' => $periodo->codigo]) }}"
                   class="co-btn co-btn-muted">Atrás</a>
            @else
                <a href="{{ route('courier.index', ['periodo' => $periodo->codigo]) }}"
                   class="co-btn co-btn-muted">Volver al inicio</a>
            @endif
        </div>

        <div>
            @php
                $siguiente = $paso['siguiente'];
                $faltaCalcular = $siguiente
                    && \App\Http\Controllers\CourierPagoController::PASOS[$siguiente]['requiere_calculo']
                    && ! $paso['calculado'];
            @endphp

            @if(! $siguiente)
                <a href="{{ route('courier.index', ['periodo' => $periodo->codigo]) }}"
                   class="co-btn co-btn-muted">Terminar</a>
            @elseif($faltaCalcular)
                {{-- Entre mirar los datos y mirar montos hay un paso que no es de navegación. --}}
                <form method="POST" action="{{ route('courier.calcular') }}" data-upload>
                    @csrf
                    <input type="hidden" name="periodo" value="{{ $periodo->codigo }}">
                    <button type="submit" class="co-btn co-btn-primary">Calcular el pago</button>
                </form>
            @else
                <a href="{{ route('courier.paso', ['paso' => $siguiente, 'periodo' => $periodo->codigo]) }}"
                   class="co-btn co-btn-primary">Siguiente</a>
            @endif
        </div>
    </footer>

</section>
