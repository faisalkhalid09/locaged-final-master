@extends('layouts.app')

@section('content')
    <div class="mt-3 position-relative mb-5">
        <!-- Overview Section -->
        <div class="overview-section px-3 px-md-0">
            <div class="d-flex justify-content-between">
                <h2>{{ ui_t('pages.dashboard.overview') }}</h2>
            </div>
            @include('components.stats-cards')
        </div>

        <!-- Main Dashboard Content -->
        <div class="row">
            <div class="col-lg-6 col-md-12">
                <div class="left-column">
                    <!-- Categories Section -->
                    @include('components.categories-stats')
                </div>
            </div>
            <div class="col-lg-6 col-md-12 mt-5 mt-lg-0">
                <!-- Chart -->
                @include('components.documents-chart')
            </div>
        </div>

        <!-- Secondary row: charts/cards -->
        <div class="row mt-4">
            <div class="col-lg-6 col-md-12 mb-3 mb-lg-0">
                @include('components.doc-types-donut')
            </div>
            <div class="col-lg-6 col-md-12">
                @include('components.rooms-cards')
            </div>
        </div>

        <!-- Pending approvals Section -->
        @can('viewAny', \App\Models\Document::class)
            <div class="mt-5">
                <h3 class="mb-3">{{ ui_t('pages.dashboard.approvals') }}</h3>
                <livewire:documents-table :showOnlyPendingApprovals="true" />
            </div>
        @endcan

        
    </div>
@endsection
