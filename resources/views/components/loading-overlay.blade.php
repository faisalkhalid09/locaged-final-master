@props(['message' => 'Processing...', 'target' => null])

<div 
    wire:loading 
    @if($target) wire:target="{{ $target }}" @endif
    class="loading-overlay position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center"
    style="background-color: rgba(0, 0, 0, 0.7); z-index: 9999;"
>
    <div class="text-center text-white">
        <div class="spinner-border mb-3" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Loading...</span>
        </div>
        <h4 class="fw-bold">{{ $message }}</h4>
    </div>
</div>
