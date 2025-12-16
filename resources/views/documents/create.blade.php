@extends('layouts.app')

@section('content')
    <div class="pt-2">
        @livewire('multiple-documents-create-form', [
            'folderId'   => request('folder_id'),
            'categoryId' => request('category_id'),
        ])
    </div>
@endsection




