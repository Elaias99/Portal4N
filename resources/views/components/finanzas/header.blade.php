@props([
    'backRoute',
    'title',
    'backLabel' => 'Volver al Panel Principal',
])

<div class="mb-3">
    <a href="{{ $backRoute }}" class="btn btn-outline-secondary btn-sm">
        <i class="fa fa-arrow-left"></i> {{ $backLabel }}
    </a>
</div>

<h1 class="text-center mb-4">{{ $title }}</h1>