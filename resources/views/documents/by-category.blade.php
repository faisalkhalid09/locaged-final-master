@extends('layouts.app')


@section('content')
    <div class=" mt-4">
        <div class=" d-flex justify-content-between mb-5">
            <div class="d-flex align-items-center all-cat">
                <a href="{{ route('documents.all') }}">
                    <h4 class="me-3">{{ ui_t('nav.documents') }}  <i class="fa-solid fa-angle-right"></i> </h4>
                </a>
                <a href="{{ route('categories.index') }}">
                    <h5 class="me-3">{{ ui_t('pages.categories_page.all_categories') }} <i class="fa-solid fa-angle-right"></i></h5>
                </a>

                <h5 class="me-3">{{ $category ? $category->name : ui_t('nav.all_documents') }}</h5>
            </div>
        </div>

        <livewire:documents-by-category-table
            :filter-id="$category?->id"
            :is-category="true"
            :context-label="$category?->name"
        />
    </div>



@endsection
