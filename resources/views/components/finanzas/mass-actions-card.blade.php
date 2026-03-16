@props([
    'title' => 'Gestión Masiva',
])

<div class="card shadow-sm border-0 h-100">
    <div class="card-body text-center d-flex flex-column justify-content-center">
        <h6 class="fw-bold mb-3">{{ $title }}</h6>
        {{ $slot }}
    </div>
</div>