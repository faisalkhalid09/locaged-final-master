<div>
    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2 small">{{ __('Total Deleted') }}</h6>
                            <h3 class="mb-0">{{ number_format($totalDeleted) }}</h3>
                        </div>
                        <div class="bg-danger bg-opacity-10 rounded-circle p-3">
                            <i class="fas fa-trash-alt text-danger fa-lg"></i>
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
                            <h6 class="text-muted mb-2 small">{{ __('This Week') }}</h6>
                            <h3 class="mb-0">{{ number_format($thisWeekDeleted) }}</h3>
                        </div>
                        <div class="bg-warning bg-opacity-10 rounded-circle p-3">
                            <i class="fas fa-calendar-week text-warning fa-lg"></i>
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
                            <h6 class="text-muted mb-2 small">{{ __('Today') }}</h6>
                            <h3 class="mb-0">{{ number_format($todayDeleted) }}</h3>
                        </div>
                        <div class="bg-info bg-opacity-10 rounded-circle p-3">
                            <i class="fas fa-calendar-day text-info fa-lg"></i>
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
                            <h6 class="text-muted mb-2 small">{{ __('Current Page') }}</h6>
                            <h3 class="mb-0">{{ $logs->count() }}</h3>
                        </div>
                        <div class="bg-primary bg-opacity-10 rounded-circle p-3">
                            <i class="fas fa-file-alt text-primary fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Section - All in one line -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <div class="d-flex flex-wrap align-items-end gap-2">
                <!-- Document Search -->
                <div style="flex: 1; min-width: 180px;">
                    <label class="form-label small mb-1">{{ __('Document') }}</label>
                    <input type="text" class="form-control form-control-sm" placeholder="{{ __('Search...') }}" wire:model.live.debounce.300ms="search" />
                </div>

                <!-- Creation Date -->
                <div style="min-width: 130px;">
                    <label class="form-label small mb-1">{{ __('Creation Date') }}</label>
                    <input type="date" class="form-control form-control-sm" wire:model.change="creationDate" />
                </div>

                <!-- Expiration Date -->
                <div style="min-width: 130px;">
                    <label class="form-label small mb-1">{{ __('Expiration') }}</label>
                    <input type="date" class="form-control form-control-sm" wire:model.change="expirationDate" />
                </div>

                <!-- Deleted At Date -->
                <div style="min-width: 130px;">
                    <label class="form-label small mb-1">{{ __('Deleted At') }}</label>
                    <input type="date" class="form-control form-control-sm" wire:model.change="deletedAt" />
                </div>

                <!-- Deleted By (User) -->
                <div style="min-width: 140px;">
                    <label class="form-label small mb-1">{{ __('Deleted By') }}</label>
                    <select class="form-select form-select-sm" wire:model.change="deletedBy">
                        <option value="">{{ __('All Users') }}</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->full_name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Structure (Department) -->
                <div style="min-width: 140px;">
                    <label class="form-label small mb-1">{{ __('Structure') }}</label>
                    <select class="form-select form-select-sm" wire:model.change="departmentId">
                        <option value="">{{ __('All') }}</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Reset Button -->
                <div>
                    <button type="button" wire:click="resetFilters" class="btn btn-sm btn-outline-danger">
                        <i class="fas fa-redo"></i> {{ __('Reset Filters') }}
                    </button>
                </div>

                <!-- Per Page & Export -->
                <div class="d-flex align-items-center gap-2 ms-auto">
                    <label class="form-label small mb-0">{{ __('Per page') }}</label>
                    <select class="form-select form-select-sm" wire:model.change="perPage" style="width: 70px;">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <button type="button" wire:click="export" class="btn btn-sm btn-success">
                        <i class="fas fa-file-export"></i> {{ __('Export') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Section -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 250px;">{{ __('Document') }}</th>
                            <th style="width: 120px;">{{ __('Creation Date') }}</th>
                            <th style="width: 100px;">{{ __('Expiration') }}</th>
                            <th style="width: 120px;">{{ __('Deleted At') }}</th>
                            <th style="width: 140px;">{{ __('Deleted By') }}</th>
                            <th>{{ __('Structure') }}</th>
                            <th style="width: 60px;" class="text-center">{{ __('PDF') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            @php
                                $doc = $log->document;
                                $dept = $doc?->department;
                                $service = $doc?->service;
                                $subDept = $service?->subDepartment;
                            @endphp
                            <tr>
                                {{-- Document Name --}}
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0 me-2">
                                            <div class="bg-danger bg-opacity-10 rounded p-2">
                                                <i class="fas fa-file-times text-danger"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 min-width-0">
                                            <div class="fw-semibold text-truncate" style="max-width: 180px;" title="{{ $doc?->title ?? __('(Document deleted)') }}">
                                                {{ $doc?->title ?? __('(Document deleted)') }}
                                            </div>
                                            <div class="text-muted small">ID: {{ $log->document_id }}</div>
                                        </div>
                                    </div>
                                </td>
                                {{-- Creation Date --}}
                                <td>
                                    <div class="small">
                                        <div class="fw-semibold">{{ $doc?->created_at?->format('Y-m-d') ?? '—' }}</div>
                                    </div>
                                </td>
                                {{-- Date of Expiration --}}
                                <td>
                                    @if($doc?->expire_at)
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                                            {{ $doc->expire_at->format('Y-m-d') }}
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                {{-- Date of Deletion --}}
                                <td>
                                    <div class="small">
                                        <div class="fw-semibold text-danger">{{ optional($log->occurred_at)->format('Y-m-d') ?? '—' }}</div>
                                        <div class="text-muted">{{ optional($log->occurred_at)->format('H:i') ?? '' }}</div>
                                    </div>
                                </td>
                                {{-- Deleted By --}}
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
                                            <div class="fw-semibold small text-truncate" style="max-width: 120px;">
                                                {{ $log->user?->full_name ?? 'N/A' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                {{-- Structure (Hierarchy) --}}
                                <td>
                                    <div class="d-flex flex-wrap gap-1 align-items-center">
                                        @if($dept)
                                            <span class="badge bg-primary-subtle text-primary px-2 py-1" style="font-size: 0.7rem;" title="{{ __('Pole') }}: {{ $dept->name }}">
                                                {{ Str::limit($dept->name, 15) }}
                                            </span>
                                        @endif
                                        @if($subDept)
                                            <i class="fas fa-chevron-right text-muted" style="font-size: 0.5rem;"></i>
                                            <span class="badge bg-secondary-subtle text-secondary px-2 py-1" style="font-size: 0.7rem;" title="{{ __('Dep') }}: {{ $subDept->name }}">
                                                {{ Str::limit($subDept->name, 15) }}
                                            </span>
                                        @endif
                                        @if($service)
                                            <i class="fas fa-chevron-right text-muted" style="font-size: 0.5rem;"></i>
                                            <span class="badge bg-info-subtle text-info px-2 py-1" style="font-size: 0.7rem;" title="{{ __('Service') }}: {{ $service->name }}">
                                                {{ Str::limit($service->name, 15) }}
                                            </span>
                                        @endif
                                        @if(!$dept && !$subDept && !$service)
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </div>
                                </td>
                                {{-- PDF Export Button --}}
                                <td class="text-center">
                                    <button type="button" 
                                        wire:click="exportSinglePdf({{ $log->id }})" 
                                        class="btn btn-sm btn-outline-danger" 
                                        title="{{ __('Export to PDF') }}">
                                        <i class="fas fa-file-pdf"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <div class="text-muted">{{ __('No permanently deleted documents found.') }}</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white border-top">
            <x-pagination :items="$logs" />
        </div>
    </div>
</div>
