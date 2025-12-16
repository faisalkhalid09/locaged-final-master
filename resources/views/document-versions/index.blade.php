@extends('layouts.app')

@section('content')
    <div class=" mt-3 position-relative ">
        <div class="d-flex justify-content-between mt-4">
            <h3>{{ ui_t('pages.versions.history') }}</h3>
           {{-- <div class=" mb-3">
                <button class="close-btn"><i class="fa-solid fa-x me-2"></i>close</button>
            </div>--}}
        </div>
        <livewire:documents-version-table/>
    </div>


@endsection
