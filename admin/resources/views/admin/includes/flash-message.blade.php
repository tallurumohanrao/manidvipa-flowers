@php
    $adminFlashMessages = collect([
        'success' => session('success') ?: session('status'),
        'danger' => session('error') ?: session('fail'),
        'warning' => session('warning'),
        'info' => session('info'),
    ])->filter(fn ($message) => filled($message));

    $adminValidationMessages = collect($errors?->getBags() ?? [])
        ->flatMap(fn ($bag) => $bag->all())
        ->filter()
        ->unique()
        ->values();
@endphp

@if($adminFlashMessages->isNotEmpty() || $adminValidationMessages->isNotEmpty())
<div class="container-fluid px-4 admin-feedback-region" aria-live="polite" aria-atomic="true">
    @foreach($adminFlashMessages as $type => $message)
        <div class="alert alert-{{ $type }} alert-dismissible fade show shadow-sm admin-feedback-alert" role="alert">
            <i class="fas {{ $type === 'success' ? 'fa-check-circle' : ($type === 'danger' ? 'fa-exclamation-circle' : ($type === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle')) }} mr-2" aria-hidden="true"></i>
            <span>{{ $message }}</span>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endforeach

    @if($adminValidationMessages->isNotEmpty())
        <div class="alert alert-danger alert-dismissible fade show shadow-sm admin-feedback-alert" role="alert">
            <div class="d-flex align-items-start">
                <i class="fas fa-exclamation-circle mr-2 mt-1" aria-hidden="true"></i>
                <div>
                    <strong>Please correct the following:</strong>
                    <ul class="mb-0 mt-1 pl-3">
                        @foreach($adminValidationMessages as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif
</div>
@endif
