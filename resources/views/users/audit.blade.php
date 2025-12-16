@extends('layouts.app')

@section('content')

    <div class="activity-log px-4 px-md-0 position-relative">
        <div class="d-md-flex mt-5 mb-4">
            <h4 class="mb-4">{{ ui_t('pages.activity_log.user_audit_title') ?? ui_t('pages.activity_log.user_audit_title') }}</h4>
            <div class="btn-group2 mb-2 mx-auto">
                <button class="me-4">
                    <a href="{{ route('users.logs') }}" class="text-decoration-none">{{ ui_t('pages.activity_log.activity_log') ?? ui_t('pages.activity_log.activity_log') }}</a>
                </button>
                <button class="me-4">
                    <a href="{{ route('documents.index') }}" class="text-decoration-none">{{ ui_t('pages.file_audit') }}</a>
                </button>
                <button class="me-4 button-active2">
                    <a href="{{ route('users.audit') }}" class="text-decoration-none">{{ ui_t('pages.activity_log.user_audit_title') ?? ui_t('pages.activity_log.user_audit_title') }}</a>
                </button>
            </div>
        </div>

        <!-- Export Button -->
        <div class="mb-3 text-end">
            <a href="{{ route('users.export') }}" class="btn btn-success btn-sm">
                <i class="fas fa-file-excel me-2"></i>{{ __('Export') }}
            </a>
        </div>

        @php
            $totalUsers = \App\Models\User::count();
            $activeUsers = \App\Models\User::whereHas('auditLogs', function($q) {
                $q->whereDate('occurred_at', '>=', now()->subDays(30));
            })->distinct()->count('users.id');
            $todayUsers = \App\Models\User::whereDate('created_at', today())->count();
            $totalDepartments = \App\Models\Department::count();
        @endphp

        <!-- Statistics Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2 small">{{ ui_t('pages.users_page.total_users') }}</h6>
                                <h3 class="mb-0">{{ number_format($totalUsers) }}</h3>
                            </div>
                            <div class="bg-primary bg-opacity-10 rounded-circle p-3">
                                <i class="fas fa-users text-primary fa-lg"></i>
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
                                <h6 class="text-muted mb-2 small">{{ ui_t('pages.activity_log.active_users_30d') }}</h6>
                                <h3 class="mb-0">{{ number_format($activeUsers) }}</h3>
                            </div>
                            <div class="bg-success bg-opacity-10 rounded-circle p-3">
                                <i class="fas fa-user-check text-success fa-lg"></i>
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
                                <h6 class="text-muted mb-2 small">{{ ui_t('pages.activity_log.new_today') }}</h6>
                                <h3 class="mb-0">{{ number_format($todayUsers) }}</h3>
                            </div>
                            <div class="bg-info bg-opacity-10 rounded-circle p-3">
                                <i class="fas fa-user-plus text-info fa-lg"></i>
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
                                <h6 class="text-muted mb-2 small">{{ ui_t('pages.reports.departments') }}</h6>
                                <h3 class="mb-0">{{ number_format($totalDepartments) }}</h3>
                            </div>
                            <div class="bg-warning bg-opacity-10 rounded-circle p-3">
                                <i class="fas fa-building text-warning fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <livewire:users-table/>


    </div>


@endsection
