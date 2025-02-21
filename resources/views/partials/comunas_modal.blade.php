{{-- <!-- resources/views/partials/comunas_modal.blade.php -->

<div class="modal fade" id="comunasModal" tabindex="-1" role="dialog" aria-labelledby="comunasModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="comunasModalLabel">Seleccionar Comuna</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="text" id="comunaSearch" class="form-control mb-3" placeholder="Buscar comuna...">
                <div class="accordion" id="accordionRegions">
                    @foreach ($regiones as $region)
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading{{ $region->id }}">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $region->id }}" aria-expanded="false" aria-controls="collapse{{ $region->id }}">
                                    {{ $region->Nombre }}
                                </button>
                            </h2>
                            <div id="collapse{{ $region->id }}" class="accordion-collapse collapse" aria-labelledby="heading{{ $region->id }}" data-bs-parent="#accordionRegions">
                                <div class="accordion-body">
                                    @foreach ($region->comunas as $comuna)
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="comuna_id" id="comuna_{{ $comuna->id }}" value="{{ $comuna->id }}" {{ (isset($empleado->comuna_id) && $empleado->comuna_id == $comuna->id) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="comuna_{{ $comuna->id }}">
                                                {{ $comuna->Nombre }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" data-dismiss="modal">Seleccionar</button>
            </div>
        </div>
    </div>
</div>



{{-- JAVASCRIPT --}}

{{-- <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('comunaSearch').addEventListener('keyup', function() {
            var searchValue = this.value.toLowerCase();
            var regiones = document.querySelectorAll('.accordion-item');
    
            regiones.forEach(function(region) {
                var comunas = region.querySelectorAll('.form-check');
                var regionMatch = false;
    
                comunas.forEach(function(comuna) {
                    var comunaName = comuna.querySelector('label').textContent.toLowerCase();
                    if (comunaName.includes(searchValue)) {
                        comuna.style.display = '';
                        regionMatch = true;
                    } else {
                        comuna.style.display = 'none';
                    }
                });
    
                if (regionMatch) {
                    region.style.display = '';
                } else {
                    region.style.display = 'none';
                }
            });
        });
    });
    </script> --}}

<!-- resources/views/partials/comunas_modal.blade.php -->

<div class="modal fade" id="comunasModal" tabindex="-1" role="dialog" aria-labelledby="comunasModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="comunasModalLabel">Seleccionar Comuna</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="text" id="comunaSearch" class="form-control mb-3" placeholder="Buscar comuna...">
                <div class="accordion" id="accordionRegions">
                    @foreach ($regiones as $region)
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading{{ $region->id }}">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $region->id }}" aria-expanded="false" aria-controls="collapse{{ $region->id }}">
                                    {{ $region->Nombre }}
                                </button>
                            </h2>
                            <div id="collapse{{ $region->id }}" class="accordion-collapse collapse" aria-labelledby="heading{{ $region->id }}" data-bs-parent="#accordionRegions">
                                <div class="accordion-body">
                                    @foreach ($region->comunas as $comuna)
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="comuna_id" id="comuna_{{ $comuna->id }}" value="{{ $comuna->id }}" {{ old('comuna_id', $empleado->comuna_id ?? '') == $comuna->id ? 'checked' : '' }}>                                            
                                            <label class="form-check-label" for="comuna_{{ $comuna->id }}">
                                                {{ $comuna->Nombre }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" onclick="seleccionarComuna()" data-dismiss="modal">Seleccionar</button>

            </div>
        </div>
    </div>
</div>

{{-- JAVASCRIPT --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('comunaSearch').addEventListener('keyup', function() {
            var searchValue = this.value.toLowerCase();
            var regiones = document.querySelectorAll('#accordionRegions .accordion-item');
    
            regiones.forEach(function(region) {
                var comunas = region.querySelectorAll('.form-check');
                var regionMatch = false;
    
                comunas.forEach(function(comuna) {
                    var comunaName = comuna.querySelector('label').textContent.toLowerCase();
                    if (comunaName.includes(searchValue)) {
                        comuna.style.display = '';
                        regionMatch = true;
                    } else {
                        comuna.style.display = 'none';
                    }
                });
    
                if (regionMatch) {
                    region.style.display = '';
                    if (!region.querySelector('.accordion-collapse').classList.contains('show')) {
                        region.querySelector('.accordion-button').click(); // Abrir región
                    }
                } else {
                    region.style.display = 'none';
                }
            });
        });
    });
</script>
