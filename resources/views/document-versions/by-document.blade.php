@extends('layouts.app')

@section('content')
    <div class=" mt-3 position-relative ">
        <div class="d-flex justify-content-between mt-4">
            <h3>{{ ui_t('pages.versions.for_file', ['title' => $document->title]) }}</h3>
             <div class=" mb-3">
             </div>
        </div>
        <livewire:documents-version-table :documentId="$document->id" />
    </div>


@endsection
