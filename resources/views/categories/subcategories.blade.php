@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        <div class="d-flex justify-content-between mb-5">
            <div class="d-flex align-items-center all-cat">
                <a href="{{ route('documents.all') }}">
                    <h4 class="me-3">{{ ui_t('pages.categories_sub.documents') }}  <i class="fa-solid fa-angle-right"></i></h4>
                </a>
                <a href="{{ route('categories.index') }}">
                    <h5 class="me-3">{{ ui_t('pages.categories_sub.all_categories') }} <i class="fa-solid fa-angle-right"></i></h5>
                </a>
                <h5 class="me-3">{{ $category->name }}</h5>
            </div>
        </div>


        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4 g-4 mb-5">
            @php
                $colors = [
                    ['bar' => '#f0d672', 'icon' => 'assets/Group 634.svg'], // Yellow
                    ['bar' => '#e63946', 'icon' => 'assets/Group 6.svg'],   // Red
                    ['bar' => '#47a778', 'icon' => 'assets/Group 8.svg'],   // Green
                    ['bar' => '#68a0fd', 'icon' => 'assets/Clip path group.svg'], // Blue
                ];
            @endphp
            @foreach($subcategories as $subcategory)
                <div class="col">
                    @php $color = $colors[$loop->index % count($colors)]; @endphp
                    <div class="category-card position-relative h-100">
                        <div class="category-card-bar" style="background-color: {{ $color['bar'] }}"></div>
                        <div class="category-card-actions d-flex gap-1">
                            @can('update', $subcategory->category)
                                <a href="{{ route('categories.edit', $subcategory->category) }}" class="btn btn-sm btn-light border">
                                    <i class="fa fa-edit"></i>
                                </a>
                            @endcan

                            @can('delete', $subcategory->category)
                                <button type="button"
                                        data-id="{{ $subcategory->id }}"
                                        data-name="{{ $subcategory->name }}"
                                        data-url="{{ route('subcategories.destroy', $subcategory) }}"
                                        class="btn btn-sm btn-light border trigger-action"
                                        data-method="DELETE"
                                        data-button-text="{{ ui_t('pages.categories_sub.confirm') }}"
                                        data-title="{{ ui_t('pages.categories_sub.delete_title', ['name' => $subcategory->name]) }}"
                                        data-body="{{ ui_t('pages.categories_sub.delete_body') }}">
                                    <i class="fa fa-trash text-danger"></i>
                                </button>
                            @endcan
                        </div>

                        <a href="{{ route('documents.by-subcategory', ['subcategoryId' => $subcategory->id]) }}" class="text-decoration-none stretched-link" aria-label="{{ ui_t('pages.categories_page.open_subcategory', ['name' => $subcategory->name, 'count' => $subcategory->documents_count]) }}">
                            <div class="d-flex align-items-center gap-3">
                                <div class="category-card-icon">
                                    <img src="{{ asset($color['icon']) }}" alt="folder-icon" />
                                </div>
                                <div class="info-cat text-start">
                                    <h3 class="category-card-title mb-1">{{ $subcategory->name }}</h3>
                                    <p class="category-card-count mb-0">{{ $subcategory->documents_count }} {{ ui_t('pages.categories_sub.files') }}</p>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        @if($subcategories->isEmpty())
            <div class="text-center py-5">
                <div class="mb-4">
                    <i class="fa fa-folder-open fa-3x text-muted"></i>
                </div>
                <h5 class="text-muted">{{ ui_t('pages.categories_sub.no_subcategories') }}</h5>
                <p class="text-muted">{{ ui_t('pages.categories_sub.no_subcategories_hint') }}</p>
                @can('update', $category)
                    <a href="{{ route('categories.edit', $category) }}" class="btn btn-primary">
                        <i class="fa fa-plus me-2"></i>{{ ui_t('pages.categories_sub.add_subcategories') }}
                    </a>
                @endcan
            </div>
        @endif
    </div>

    @include('components.modals.confirm-modal')
@endsection

@section('scripts')
    @parent
    <style>
        .category-card {
            background: #ffffff;
            border: 1px solid #eef0f3;
            border-radius: 12px;
            padding: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
            transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
            min-height: 96px;
        }
        .category-card-bar {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            border-radius: 12px 12px 0 0;
        }
        .category-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.06);
            border-color: #e2e8f0;
        }
        .category-card:active { transform: translateY(-1px) scale(0.997); }
        .category-card:focus-within {
            outline: none;
            box-shadow: 0 0 0 4px rgba(59,130,246,0.15);
            border-color: #cbd5e1;
        }
        .category-card-actions {
            position: absolute;
            top: 8px;
            right: 8px;
            z-index: 2;
            opacity: 0;
            visibility: hidden;
            transition: opacity .12s ease;
        }
        .category-card:hover .category-card-actions,
        .category-card:focus-within .category-card-actions {
            opacity: 1;
            visibility: visible;
        }
        .category-card-icon img {
            width: 52px;
            height: 52px;
            transition: transform .15s ease;
        }
        .category-card:hover .category-card-icon img { transform: scale(1.03); }
        .category-card-title {
            font-weight: 600;
            font-size: 16px;
            color: #1a1f36;
        }
        .category-card-count {
            font-size: 13px;
            color: #64748b;
        }
        @media (max-width: 576px) {
            .category-card { padding: 14px; }
            .category-card-icon img { width: 46px; height: 46px; }
            .category-card-title { font-size: 15px; }
            .category-card-count { font-size: 12px; }
        }
    </style>
@endsection
