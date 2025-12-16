@extends('layouts.app')

@section('content')
    <div class="mt-5">
        <div class="d-md-flex mb-4">
            <h4 class="mb-4">{{ ui_t('pages.status.title') }}</h4>
        </div>

        {{-- Use the unified documents table in approval mode with filters, selection,
             bulk approve/reject, and per-page controls. --}}
        @can('viewAny', \App\Models\Document::class)
            <livewire:documents-table :showOnlyPendingApprovals="true" />
        @endcan

        @include('components.modals.confirm-modal')
    </div>
@endsection
