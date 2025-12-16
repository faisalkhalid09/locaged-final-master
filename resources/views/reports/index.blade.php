@extends('layouts.app')

@section('content')
    <div class="activity-log px-4 px-md-0 position-relative">
        <div class="d-md-flex mt-5">
            <h4 class="mb-4">{{ ui_t('pages.reports.document_reports') }}</h4>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid mb-4">
            <a href="{{ route('documents.all') }}" class="text-decoration-none text-reset">
                <div class="stat-card red">
                    <div class="stat-icon">
                        <i class="fa-solid fa-file"></i>
                    </div>
                    <div class="stat-content">
                        <div class="d-flex justify-content-between">
                            <h3>{{ $stats['total_documents'] }}</h3>
                        </div>
                        <p>{{ ui_t('pages.stats.total_documents') }}</p>
                    </div>
                </div>
            </a>

            <a href="{{ route('documents.all', ['status' => \App\Enums\DocumentStatus::Approved->value]) }}" class="text-decoration-none text-reset">
                <div class="stat-card green">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="d-flex justify-content-between">
                            <h3>{{ $stats['by_status']['approved'] ?? 0 }}</h3>
                        </div>
                        <p>{{ ui_t('pages.stats.approved') }}</p>
                    </div>
                </div>
            </a>

            <a href="{{ route('documents.all', ['status' => \App\Enums\DocumentStatus::Pending->value]) }}" class="text-decoration-none text-reset">
                <div class="stat-card blue">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <div class="d-flex justify-content-between">
                            <h3>{{ $stats['by_status']['pending'] ?? 0 }}</h3>
                        </div>
                        <p>{{ ui_t('pages.stats.pending') }}</p>
                    </div>
                </div>
            </a>

            <a href="{{ route('departments.index') }}" class="text-decoration-none text-reset">
                <div class="stat-card yellow">
                    <div class="stat-icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="stat-content">
                        <div class="d-flex justify-content-between">
                            <h3>{{ count($stats['by_department']) }}</h3>
                        </div>
                        <p>{{ ui_t('pages.reports.departments') }}</p>
                    </div>
                </div>
            </a>
        </div>

        <!-- Filters Section -->
        <div class="d-md-flex justify-content-between my-3">
            <div class="search-files mb-3 mb-md-0">
                <i class="fas fa-search"></i>
                <input type="text" 
                       class="bg-transparent" 
                       placeholder="{{ ui_t('pages.reports.search_placeholder') }}" 
                       name="search"
                       value="{{ request('search') }}"
                       form="filter-form" />
            </div>
            <div class="d-flex align-items-center gap-2">
                <!-- Export Button -->
                <a href="{{ route('reports.export', request()->query()) }}" 
                   class="btn btn-success d-flex align-items-center gap-2">
                    <i class="fas fa-download"></i>
                    {{ ui_t('pages.reports.export_csv') }}
                </a>
                
                <!-- Filter Toggle Button -->
                <button class="btn border p-2 text-muted" 
                        type="button" 
                        data-bs-toggle="collapse" 
                        data-bs-target="#filtersCollapse" 
                        aria-expanded="false">
                    <i class="fas fa-sliders-h"></i>
                </button>
            </div>
        </div>

        <!-- Collapsible Filters -->
        <div class="collapse mb-4" id="filtersCollapse">
            <div class="card">
                <div class="card-body">
                    <form id="filter-form" method="GET" action="{{ route('reports.index') }}">
                        <div class="row g-3">
                            <!-- Room Filter -->
                            <div class="col-md-2">
                                <label for="room" class="form-label">{{ ui_t('pages.reports.filters.room') }}</label>
                                <select name="room" id="room" class="form-select">
                                    <option value="">{{ ui_t('pages.reports.filters.all_rooms') }}</option>
                                    @foreach($rooms as $room)
                                        <option value="{{ $room }}" {{ request('room') == $room ? 'selected' : '' }}>
                                            {{ $room }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Structure Filter (was Department) -->
                            <div class="col-md-2">
                                <label for="department_id" class="form-label">Structure</label>
                                <select name="department_id" id="department_id" class="form-select">
                                    <option value="">All Structure</option>
                                    @foreach($departments as $department)
                                        <option value="{{ $department->id }}" {{ request('department_id') == $department->id ? 'selected' : '' }}>
                                            {{ $department->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- User Filter (Super admin only) -->
                            @if(auth()->user()->hasRole(['master', 'super_admin', 'super administrator']))
                            <div class="col-md-2">
                                <label for="user_id" class="form-label">{{ ui_t('pages.reports.filters.user') }}</label>
                                <select name="user_id" id="user_id" class="form-select">
                                    <option value="">{{ ui_t('pages.reports.filters.all_users') }}</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                            {{ $user->full_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @endif

                            <!-- Year Filter -->
                            <div class="col-md-2">
                                <label for="year" class="form-label">{{ ui_t('pages.reports.filters.year') }}</label>
                                <select name="year" id="year" class="form-select">
                                    <option value="">{{ ui_t('pages.reports.filters.all_years') }}</option>
                                    @foreach($years as $year)
                                        <option value="{{ $year }}" {{ request('year') == $year ? 'selected' : '' }}>
                                            {{ $year }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Department Filter (was Sub-department) -->
                            <div class="col-md-2">
                                <label for="sub_department_id" class="form-label">Department</label>
                                <select name="sub_department_id" id="sub_department_id" class="form-select">
                                    <option value="">All departments</option>
                                    @foreach($subDepartments as $subDepartment)
                                        <option value="{{ $subDepartment->id }}" {{ request('sub_department_id') == $subDepartment->id ? 'selected' : '' }}>
                                            {{ $subDepartment->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Filter Buttons -->
                            <div class="col-md-2 d-flex align-items-end gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter"></i> {{ ui_t('pages.reports.apply') }}
                                </button>
                                <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i> {{ ui_t('pages.reports.clear') }}
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Results Table -->
        <div class="recent-files-section">
            <table class="files-table">
                <thead>
                    <tr>
                        <th>{{ ui_t('tables.file_name') }}</th>
                        <th>{{ ui_t('pages.reports.table.department') }}</th>
                        <th>{{ ui_t('pages.reports.table.created_by') }}</th>
                        <th>{{ ui_t('pages.reports.table.created_at') }}</th>
                        <th>{{ ui_t('tables.physical_location') }}</th>
                        <th>{{ ui_t('tables.status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($documents as $doc)
                        <tr>
                            <td>
                                <div class="file-item">
                                    <div class="file-icon">
                                        @if($doc->latestVersion)
                                            @php
                                                $extension = strtolower(pathinfo($doc->latestVersion->file_path, PATHINFO_EXTENSION));
                                                $iconClass = getFileIcon($extension);
                                            @endphp
                                            <i class="{{ $iconClass }}" style="font-size: 24px;"></i>
                                        @else
                                            <i class="fas fa-file text-secondary" style="font-size: 24px;"></i>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="file-name text-truncate" style="max-width: 260px;" title="{{ $doc->title }}">
                                            {{ $doc->title }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div>{{ $doc->department?->name ?? ui_t('pages.activity.table.na') }}</div>
                            </td>
                            <td>
                                <div>{{ $doc->createdBy?->full_name ?? ui_t('pages.activity.table.na') }}</div>
                            </td>
                            <td>
                                <div class="file-date">
                                    {{ $doc->created_at->format('d/m/Y') }}
                                    <br/>{{ $doc->created_at->format('H:i') }}
                                </div>
                            </td>
                            <td>
                                <div>{{ $doc->box ? $doc->box->__toString() : ui_t('pages.activity.table.na') }}</div>
                            </td>
                            <td>
                                <button class="status-badge approved border-0">
                                    {{ ui_t('pages.documents.status.' . $doc->status) }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <div class="text-muted">
                                    <i class="fas fa-inbox fa-3x mb-3"></i>
                                    <p>{{ ui_t('pages.reports.empty') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <x-pagination :items="$documents"></x-pagination>
        </div>
    </div>
@endsection
