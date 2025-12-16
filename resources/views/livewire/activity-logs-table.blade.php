<div>
    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2 small">{{ ui_t('pages.activity.cards.total_logs') }}</h6>
                            <h3 class="mb-0">{{ number_format($totalLogs) }}</h3>
                        </div>
                        <div class="bg-primary bg-opacity-10 rounded-circle p-3">
                            <i class="fas fa-list text-primary fa-lg"></i>
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
                            <h6 class="text-muted mb-2 small">{{ ui_t('pages.activity.cards.todays_logs') }}</h6>
                            <h3 class="mb-0">{{ number_format($todayLogs) }}</h3>
                        </div>
                        <div class="bg-success bg-opacity-10 rounded-circle p-3">
                            <i class="fas fa-calendar-day text-success fa-lg"></i>
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
                            <h6 class="text-muted mb-2 small">{{ ui_t('pages.activity.cards.this_week') }}</h6>
                            <h3 class="mb-0">{{ number_format($thisWeekLogs) }}</h3>
                        </div>
                        <div class="bg-info bg-opacity-10 rounded-circle p-3">
                            <i class="fas fa-calendar-week text-info fa-lg"></i>
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
                            <h6 class="text-muted mb-2 small">{{ ui_t('pages.activity.cards.active_users') }}</h6>
                            <h3 class="mb-0">{{ number_format($uniqueUsers) }}</h3>
                        </div>
                        <div class="bg-warning bg-opacity-10 rounded-circle p-3">
                            <i class="fas fa-users text-warning fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4 border-bottom-0">
        <li class="nav-item">
            <a class="nav-link {{ $logType === 'documents' ? 'active fw-bold border-bottom-0' : '' }}" 
               href="#" wire:click.prevent="setLogType('documents')"
               style="{{ $logType === 'documents' ? 'border-top: 3px solid var(--bs-primary);' : 'color: var(--bs-secondary);' }}">
                <i class="fas fa-file-alt me-2"></i>Document Activity
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $logType === 'authentication' ? 'active fw-bold border-bottom-0' : '' }}" 
               href="#" wire:click.prevent="setLogType('authentication')"
               style="{{ $logType === 'authentication' ? 'border-top: 3px solid var(--bs-primary);' : 'color: var(--bs-secondary);' }}">
                <i class="fas fa-user-shield me-2"></i>Authentication Activity
            </a>
        </li>
    </ul>

    <!-- Filters Section -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-12">
                    <div class="search-files">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="{{ ui_t('pages.activity.filters.search_placeholder') }}" wire:model.live.debounce.300ms="search" />
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">{{ ui_t('pages.activity.filters.date_from') }}</label>
                    <input type="date" class="form-control" wire:model.change="dateFrom" />
                </div>
                <div class="col-md-3">
                    <label class="form-label small">{{ ui_t('pages.activity.filters.date_to') }}</label>
                    <input type="date" class="form-control" wire:model.change="dateTo" />
                </div>
                <div class="col-md-2">
                    <label class="form-label small">{{ ui_t('pages.activity.filters.user') }}</label>
                    <select class="form-select" wire:model.change="userId">
                        <option value="">{{ ui_t('pages.activity.filters.all_users') }}</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                
                @if($logType === 'documents')
                    <div class="col-md-2">
                        <label class="form-label small">{{ ui_t('pages.activity.filters.department') }}</label>
                        <select class="form-select" wire:model.change="departmentId">
                            <option value="">{{ ui_t('pages.activity.filters.all_departments') }}</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}">{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="col-md-2">
                    <label class="form-label small">{{ ui_t('pages.activity.filters.action_type') }}</label>
                    <select class="form-select" wire:model.change="actionType">
                        <option value="">{{ ui_t('pages.activity.filters.all_actions') }}</option>
                        @if($logType === 'documents')
                            <option value="created">{{ ui_t('pages.activity.actions.created') }}</option>
                            <option value="updated">{{ ui_t('pages.activity.actions.updated') }}</option>
                            <option value="approved">{{ ui_t('pages.activity.actions.approved') }}</option>
                            <option value="declined">{{ ui_t('pages.activity.actions.declined') }}</option>
                            <option value="archived">{{ ui_t('pages.activity.actions.archived') }}</option>
                            <option value="deleted">{{ ui_t('pages.activity.actions.deleted') }}</option>
                            <option value="permanently_deleted">{{ ui_t('pages.activity.actions.permanently_deleted') }}</option>
                            <option value="downloaded">{{ ui_t('pages.activity.actions.downloaded') }}</option>
                            <option value="viewed">{{ ui_t('pages.activity.actions.viewed') }}</option>
                            <option value="renamed">{{ ui_t('pages.activity.actions.renamed') }}</option>
                            <option value="locked">{{ ui_t('pages.activity.actions.locked') }}</option>
                            <option value="unlocked">{{ ui_t('pages.activity.actions.unlocked') }}</option>
                            <option value="moved">{{ ui_t('pages.activity.actions.moved') }}</option>
                            <option value="destroyed">{{ ui_t('pages.activity.actions.destroyed') }}</option>
                            <option value="failed_access">{{ ui_t('pages.activity.actions.failed_access') }}</option>
                        @else
                            <option value="login_success">Login Success</option>
                            <option value="login_failed">Login Failed</option>
                            <option value="logout">Logout</option>
                        @endif
                    </select>
                </div>
                <div class="col-md-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <button type="button" wire:click="resetFilters" class="btn btn-sm btn-outline-danger">
                                <i class="fas fa-redo me-1"></i> {{ ui_t('pages.activity.reset_filters') }}
                            </button>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <label class="form-label small mb-0">{{ ui_t('pages.activity.filters.per_page') }}</label>
                            <select class="form-select form-select-sm" wire:model.change="perPage" style="width: auto;">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                            <button type="button" wire:click="export" class="btn btn-sm btn-success">
                                <i class="fas fa-file-export me-1"></i> {{ ui_t('pages.activity.filters.export') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Section -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 150px;">{{ ui_t('pages.activity.table.date_time') }}</th>
                            <th style="width: 150px;">{{ ui_t('pages.activity.table.user') }}</th>
                            @if($logType === 'documents')
                                <th style="width: 120px;">{{ ui_t('pages.activity.table.action') }}</th>
                                <th>{{ ui_t('pages.activity.table.document') }}</th>
                            @else
                                <th>Email</th>
                                <th style="width: 120px;">Type</th>
                            @endif
                            <th style="width: 150px;">{{ ui_t('pages.activity.table.ip_device') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td>
                                    <div class="small">
                                        <div class="fw-semibold">{{ $log->occurred_at?->format('Y-m-d') }}</div>
                                        <div class="text-muted">{{ $log->occurred_at?->format('H:i:s') }}</div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        @php
                                            $userImageUrl = $log->user?->avatar_url ?? asset('assets/user.png');
                                        @endphp
                                        <div class="flex-shrink-0">
                                            <img
                                                src="{{ $userImageUrl }}"
                                                alt="{{ $log->user?->full_name ?? 'User' }}"
                                                class="rounded-circle"
                                                style="width: 32px; height: 32px; object-fit: cover;"
                                                onerror="this.onerror=null;this.src='{{ asset('assets/user.png') }}';"
                                            />
                                        </div>
                                        <div class="flex-grow-1 ms-2">
                                            <div class="fw-semibold small">{{ $log->user?->full_name ?? ui_t('pages.activity.table.na') }}</div>
                                            @if($log->user?->departments->first())
                                                <div class="text-muted" style="font-size: 0.75rem;">{{ $log->user->departments->first()->name }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                
                                @if($logType === 'documents')
                                    <td>
                                        <span class="badge 
                                            @if(in_array($log->action, ['created', 'approved', 'updated', 'downloaded', 'viewed', 'renamed', 'unlocked', 'moved', 'viewed_ocr'])) bg-success-subtle text-success
                                            @elseif(in_array($log->action, ['declined', 'failed_access'])) bg-danger-subtle text-danger
                                            @elseif(in_array($log->action, ['permanently_deleted', 'destroyed', 'deleted'])) bg-dark-subtle text-dark
                                            @elseif(in_array($log->action, ['archived', 'locked'])) bg-warning-subtle text-warning
                                            @else bg-secondary-subtle text-secondary
                                            @endif rounded-pill px-2 py-1">
                                            {{ ui_t('pages.activity.actions.' . $log->action) ?? ucfirst(str_replace('_', ' ', $log->action)) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($log->document)
                                            <a href="{{ route('document-versions.by-document', ['id' => $log->document->id]) }}" class="text-decoration-none">
                                                <div class="fw-semibold text-primary">{{ \Str::limit($log->document->title, 40) }}</div>
                                            </a>
                                            @if($log->document->department)
                                                <div class="text-muted small">{{ $log->document->department->name }}</div>
                                            @endif
                                        @else
                                            <span class="text-muted">{{ ui_t('pages.activity.table.na') }}</span>
                                        @endif
                                    </td>
                                @else
                                    <td>
                                        <div class="small">{{ $log->email }}</div>
                                    </td>
                                    <td>
                                        <span class="badge 
                                            @if($log->type === 'login_success') bg-success-subtle text-success
                                            @elseif($log->type === 'login_failed') bg-danger-subtle text-danger
                                            @else bg-secondary-subtle text-secondary
                                            @endif rounded-pill px-2 py-1">
                                            {{ ucfirst(str_replace('_', ' ', $log->type)) }}
                                        </span>
                                    </td>
                                @endif

                                <td>
                                    <div class="small">
                                        <div class="fw-semibold">{{ $log->ip_address ?? ui_t('pages.activity.table.na') }}</div>
                                        <div class="text-muted" style="font-size: 0.7rem;">
                                            <i class="fas fa-network-wired me-1"></i>{{ \Str::limit($log->user_agent ?? 'Device Info', 20) }}
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $logType === 'documents' ? 5 : 5 }}" class="text-center py-5">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <div class="text-muted">{{ ui_t('pages.activity.empty') }}</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white border-top">
            <x-pagination :items="$logs"></x-pagination>
        </div>
    </div>
</div>

