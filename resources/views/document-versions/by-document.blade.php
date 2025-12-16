@extends('layouts.app')

@section('content')
    <div class=" mt-3 position-relative ">
        <div class="d-flex justify-content-between mt-4">
            <h3>{{ ui_t('pages.versions.for_file', ['title' => $document->title]) }}</h3>
             <div class=" mb-3">
                 <a href="{{ route('document-versions.document.create',['documentId' => $document->id]) }}" class="btn btn-dark btn-sm">
                     <i class="fa-solid fa-plus me-2"></i>
                     {{ ui_t('pages.versions.add_new') }}
                 </a>

             </div>
        </div>
        <livewire:documents-version-table :documentId="$document->id" />
    </div>


@endsection
