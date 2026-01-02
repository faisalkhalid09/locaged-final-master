@extends('layouts.app')


@section('content')
    <div class=" mt-4">
        {{-- Breadcrumb moved inside Livewire component for dynamic updates when filters change --}}

        <livewire:documents-by-category-table
            :filter-id="$category?->id"
            :is-category="true"
            :context-label="$category?->name"
        />
    </div>



@endsection
