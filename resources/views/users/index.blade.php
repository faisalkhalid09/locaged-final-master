@extends('layouts.app')

@section('content')

        <div class=" mb-3">
            <h4 class="fw-bold">{{ ui_t('pages.users_page.users') }}</h4>
            
            @php
                $currentUserCount = \App\Models\User::count();
                $maxUsers = \App\Support\Branding::getMaxUsers();
            @endphp
            
            @if($maxUsers > 0)
                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>{{ ui_t('pages.users_page.user_limit') }}</strong> {{ $currentUserCount }} / {{ $maxUsers }} {{ ui_t('pages.users_page.users') }}
                    @if($currentUserCount >= $maxUsers)
                        <span class="text-danger ms-2">
                            <i class="fas fa-exclamation-triangle"></i> {{ ui_t('pages.users_page.limit_reached') }}
                        </span>
                    @endif
                </div>
            @else
                <div class="alert alert-success mt-3">
                    <i class="fas fa-users me-2"></i>
                    <strong>{{ ui_t('pages.users_page.total_users') }}</strong> {{ $currentUserCount }} ({{ ui_t('pages.users_page.unlimited') }})
                </div>
            @endif

            <div class="d-flex justify-content-between mt-4 pb-2 border-bottom mb-4 notification-info w-100">
                @can('viewAny',\Spatie\Permission\Models\Role::class)
                <div class=" d-flex gap-2  ">
                        <a href="{{ route('roles.index') }}" class="btn btn-sm btn-add">{{ ui_t('pages.users_page.roles') }}</a>

                </div>
                @endcan

                <div class="d-flex gap-2">
                    @can('viewAny', \App\Models\User::class)
                        <a href="{{ route('users.export') }}" class="btn btn-sm btn-outline-dark">
                            <i class="fas fa-download me-1"></i>Export Users
                        </a>
                    @endcan

                    @can('create',\App\Models\User::class)
                        <div>
                            @if($maxUsers > 0 && $currentUserCount >= $maxUsers)
                                <button class="btn btn-sm btn-secondary" disabled title="{{ ui_t('pages.users_page.add_user_limit_reached_title', ['max' => $maxUsers]) }}">
                                    <i class="fas fa-ban me-1"></i>{{ ui_t('pages.users_page.add_user_limit_reached_btn') }}
                                </button>
                            @else
                                <a class="btn btn-sm btn-dark" id="nextBtn">{{ ui_t('pages.users_page.add_user') }}</a>
                            @endif
                        </div>
                    @endcan
                </div>

            </div>
        </div>

        <livewire:users-table/>

@endsection
