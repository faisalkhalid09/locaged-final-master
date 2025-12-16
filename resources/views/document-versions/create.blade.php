@extends('layouts.app')

@section('content')
    <div class="pt-2">
        @livewire('document-version-create-form',['document' => $document])
    </div>
@endsection




