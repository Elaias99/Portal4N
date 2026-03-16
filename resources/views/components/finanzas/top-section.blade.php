@props([
    'actionsWidth' => '290px',
])

<div class="d-flex justify-content-between align-items-start gap-3 mb-4" style="align-items: stretch;">
    <div class="flex-grow-1">
        {{ $filters }}
    </div>

    <div style="width: {{ $actionsWidth }}; min-width: {{ $actionsWidth }};">
        {{ $actions }}
    </div>
</div>