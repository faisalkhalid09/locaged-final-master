@php
    $alertTypes = [
        'success' => 'alert-success',
        'error'   => 'alert-danger',
        'warning' => 'alert-warning',
        'info'    => 'alert-info',
    ];
@endphp

{{-- Show validation errors --}}
@if ($errors->any())
    <div class="alert alert-danger">
        <strong>{{ ui_t('pages.errors.input_problems') }}</strong>
        <ul class="mb-0 mt-2">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- Loop through alert types and display if session has message --}}
@foreach ($alertTypes as $type => $class)
    @if (session($type))
        <div class="alert {{ $class }}">
            {!! session($type) !!}
        </div>
    @endif
@endforeach
