@props([
    'title' => '',
])

<div class="card shadow-sm border-0">
    <div class="card-body">
        @if($title)
            <h5 class="card-title mb-3">{{ $title }}</h5>
        @endif

        {{ $slot }}
    </div>
</div>