@extends('layouts.app')

@section('content')

    <div class="activity-log px-4 px-md-0 position-relative">
        <div class="d-md-flex mt-5 mb-4 align-items-center">
            <h4 class="mb-4 mb-md-0 me-md-3">{{ ui_t('pages.file_audit') }}</h4>
            <div class="flex-grow-1 d-flex justify-content-md-center">
                <div class="btn-group2 mb-2">
                    <button class="me-4">
                        <a href="{{ route('users.logs') }}" class="text-decoration-none">{{ ui_t('pages.activity_log.activity_log') }}</a>
                    </button>
                    <button class="me-4  button-active2">
                        <a href="{{ route('documents.index') }}" class="text-decoration-none">{{ ui_t('pages.file_audit') }}</a>
                    </button>
                    <button class="me-4">
                        <a href="{{ route('logs.deletions') }}" class="text-decoration-none">{{ __('Deletion log') }}</a>
                    </button>
                </div>
            </div>
        </div>

        @php
            $totalDocuments = \App\Models\Document::count();
            $pendingDocuments = \App\Models\Document::where('status', 'pending')->count();
            $approvedDocuments = \App\Models\Document::where('status', 'approved')->count();
            $todayDocuments = \App\Models\Document::whereDate('created_at', today())->count();
        @endphp

        <!-- Statistics Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2 small">{{ ui_t('pages.stats.total_documents') }}</h6>
                                <h3 class="mb-0">{{ number_format($totalDocuments) }}</h3>
                            </div>
                            <div class="bg-primary bg-opacity-10 rounded-circle p-3">
                                <i class="fas fa-file text-primary fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2 small">{{ ui_t('pages.stats.pending') }}</h6>
                                <h3 class="mb-0">{{ number_format($pendingDocuments) }}</h3>
                            </div>
                            <div class="bg-warning bg-opacity-10 rounded-circle p-3">
                                <i class="fas fa-clock text-warning fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2 small">{{ ui_t('pages.stats.approved') }}</h6>
                                <h3 class="mb-0">{{ number_format($approvedDocuments) }}</h3>
                            </div>
                            <div class="bg-success bg-opacity-10 rounded-circle p-3">
                                <i class="fas fa-check-circle text-success fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2 small">{{ ui_t('pages.stats.todays_uploads') }}</h6>
                                <h3 class="mb-0">{{ number_format($todayDocuments) }}</h3>
                            </div>
                            <div class="bg-info bg-opacity-10 rounded-circle p-3">
                                <i class="fas fa-calendar-day text-info fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <livewire:documents-table/>



@endsection
