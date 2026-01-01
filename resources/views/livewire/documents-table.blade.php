<div class="mt-5">
    @if($boxId)
        @php
            $box = \App\Models\Box::with('shelf.row.room')->find($boxId);
        @endphp
        @if($box)
            <div class="mb-4">
                <h4 class="fw-bold">
                    <i class="fas fa-box text-secondary me-2"></i>
                    {{ __('Documents in this location') }}
                </h4>
                <p class="text-muted mb-0">
                    <i class="fas fa-map-marker-alt me-1"></i>
                    <strong>{{ __('Location') }}:</strong> <code>{{ $box->__toString() }}</code>
                </p>
            </div>
        @endif
    @elseif($this->showOnlyPendingApprovals && !request()->routeIs('documents.status'))
        <div class="mb-5 mt-4">
            <h4 class="fw-bold">{{ ui_t('pages.dashboard.pending_documents') }}</h4>
        </div>
    @endif
    <div class="section-header justify-content-end">
        <div class="section-actions ">
            {{--   <i class="fa-solid fa-sliders align-self-center"></i>--}}
            <div class="d-flex align-items-center justify-content-start">
                <x-auth-session-status class="text-danger" :status="session('error')" />
                <x-auth-session-status class="text-success" :status="session('success')" />
                
                @php
                    $declinedCount = \App\Models\Document::where('status', 'declined')
                        ->where('created_by', auth()->id())
                        ->count();
                @endphp
                
                @if($declinedCount > 0)
                    <div class="alert alert-info alert-sm ms-3 mb-0 py-2 px-3">
                        <i class="fas fa-info-circle me-1"></i>
                        {{ ui_t('pages.documents.declined_alert', ['count' => $declinedCount]) }}
                    </div>
                @endif
            </div>

            @if($this->showOnlyPendingApprovals)
                @if(count($checkedDocuments) > 0)
                    @can('approve', \App\Models\Document::class)
                        <button type="button" class="btn btn-success btn-sm ms-3" wire:click="bulkApprove">
                            {{ ui_t('actions.approve') ?? 'Approve' }}
                        </button>
                    @endcan
                    @can('decline', \App\Models\Document::class)
                        <button type="button" class="btn btn-danger btn-sm ms-2" wire:click="bulkDecline">
                            {{ ui_t('actions.reject') ?? 'Reject' }}
                        </button>
                    @endcan
                @endif
            @else
                @if(request()->routeIs('documents.index'))
                    {{-- File Audit: export all documents --}}
                    <a href="{{ route('documents.export') }}" class="btn btn-success">
                        <i class="fas fa-file-excel me-2"></i>{{ ui_t('pages.documents.export') ?? 'Export' }}
                    </a>
                @else
                    {{-- Normal documents view: export selected documents --}}
                    <button
                        type="button"
                        class="btn-export"
                        wire:click="downloadSelected"
                    >
                        <i class="fa-solid fa-arrow-up-from-bracket" style="color: #e63946"></i>
                        {{ ui_t('pages.documents.export') }}
                    </button>
                    @can('create', \App\Models\Document::class)
                        <a href="{{ route('documents.create', ['folder_id' => $this->currentFolderId]) }}" class="btn btn-dark text-white d-inline-flex gap-1 align-items-center" style="font-size: 0.9rem;">
                            <i class="fas fa-plus"></i> {{ ui_t('pages.documents.upload_documents') }}
                        </a>
                    @endcan
                @endif
            @endif

        </div>
    </div>

    <div class="recent-files-section">
        {{-- Folder breadcrumb / navigation (hidden on dashboard pending-approvals view) --}}
        @unless($this->showOnlyPendingApprovals)
        <div class="mb-3 d-flex align-items-center gap-2">
            @if(!is_null($this->currentFolderId))
                <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="goUp">
                    &larr; {{ ui_t('pages.documents.back') ?? 'Up' }}
                </button>
            @endif
        </div>
        @endunless

        <div class="table-controls">
            <div class="search-files position-relative">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="{{ ui_t('pages.documents.search_by_name') }}" wire:model.debounce.300ms="search" />

                @if($showSearchDropdown && !empty($searchResults))
                    <div class="search-suggestions">
                        @foreach($searchResults as $item)
                            <button type="button"
                                    class="search-suggestion-item d-flex justify-content-between align-items-center w-100 text-start"
                                    wire:click="openSearchResult({{ $item['id'] }})">
                                <span class="text-truncate me-2">{{ $item['title'] }}</span>
                                <span class="badge bg-light text-muted text-uppercase small">{{ $item['status'] }}</span>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="table-filters">
                @unless($this->showOnlyPendingApprovals)
                    <select class="form-select" wire:model.change="status">
                        <option value="all">{{ ui_t('filters.all') }}</option>
                        @foreach(\App\Enums\DocumentStatus::activeCases() as $status)
                            <option value="{{ $status->value }}">{{ ui_t('pages.documents.status.' . $status->value) }}</option>
                        @endforeach

                    </select>

                    {{-- In normal documents view, keep the File Type filter --}}
                    <select class="form-select" wire:model.change="fileType">
                        <option value="">{{ ui_t('filters.file_type') }}</option>
                        <option value="pdf">{{ ui_t('filters.types.pdf') }}</option>
                        <option value="doc">{{ ui_t('filters.types.word') ?? ui_t('filters.types.doc') }}</option>
                        <option value="image">{{ ui_t('filters.types.image') }}</option>
                        <option value="excel">{{ ui_t('filters.types.excel') }}</option>
                        <option value="video">{{ ui_t('filters.types.video') }}</option>
                        <option value="audio">{{ ui_t('filters.types.audio') }}</option>
                    </select>
                @else
                    {{-- Approvals view: Modern hierarchy selector (Department → Sub-Department → Service) --}}
                    <div class="dropdown hierarchy-dropdown">
                        @php
                            // Determine selected label with breadcrumb path
                            $selectedLabel = 'All Hierarchy';
                            $selectedIcon = 'fa-sitemap';
                            if ($hierarchy && isset($hierarchyDepartments)) {
                                [$hType, $hId] = explode(':', $hierarchy) + [null, null];
                                $hId = (int) $hId;
                                if ($hType === 'department') {
                                    $dept = $hierarchyDepartments->firstWhere('id', $hId);
                                    if ($dept) { 
                                        $selectedLabel = $dept->name;
                                        $selectedIcon = 'fa-building';
                                    }
                                } elseif ($hType === 'subdepartment') {
                                    foreach ($hierarchyDepartments as $dept) {
                                        $sub = optional($dept->visibleSubDepartments)->firstWhere('id', $hId);
                                        if ($sub) { 
                                            $selectedLabel = $dept->name . ' → ' . $sub->name;
                                            $selectedIcon = 'fa-layer-group';
                                            break; 
                                        }
                                    }
                                } elseif ($hType === 'service') {
                                    foreach ($hierarchyDepartments as $dept) {
                                        foreach (optional($dept->visibleSubDepartments) ?? [] as $sub) {
                                            $srv = optional($sub->visibleServices)->firstWhere('id', $hId);
                                            if ($srv) { 
                                                $selectedLabel = $dept->name . ' → ' . $sub->name . ' → ' . $srv->name;
                                                $selectedIcon = 'fa-briefcase';
                                                break 2; 
                                            }
                                        }
                                    }
                                }
                            }
                        @endphp
                        <button class="hierarchy-trigger-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas {{ $selectedIcon }} me-2"></i>
                            <span class="hierarchy-selected-text">{{ $selectedLabel }}</span>
                            <i class="fas fa-chevron-down ms-2 hierarchy-chevron"></i>
                        </button>
                        <div class="dropdown-menu hierarchy-menu">
                            <!-- Search Input -->
                            <div class="hierarchy-search-wrapper">
                                <i class="fas fa-search hierarchy-search-icon"></i>
                                <input type="text" 
                                       class="hierarchy-search-input" 
                                       placeholder="Search departments, subdepartments, services..."
                                       id="hierarchySearchInput"
                                       autocomplete="off">
                                <button type="button" class="hierarchy-search-clear" id="hierarchyClearSearch" style="display: none;">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            
                            <!-- Clear Filter removed from top -->
                            
                            <!-- Hierarchy List -->
                            <div class="hierarchy-scroll-container">
                                <ul class="hierarchy-list" id="hierarchyList">
                                    @foreach($hierarchyDepartments as $dept)
                                        <li class="hierarchy-item" data-search-text="{{ strtolower($dept->name) }}">
                                            <a href="#" class="hierarchy-link hierarchy-dept-link"
                                               wire:click.prevent="selectHierarchy('department', {{ $dept->id }})">
                                                <div class="hierarchy-link-content">
                                                    <i class="fas fa-building hierarchy-icon"></i>
                                                    <span class="hierarchy-name">{{ $dept->name }}</span>
                                                    @php
                                                        $subDepts = $dept->visibleSubDepartments ?? $dept->subDepartments ?? collect();
                                                        $subCount = $subDepts->count();
                                                    @endphp
                                                    @if($subCount > 0)
                                                        <span class="hierarchy-count">{{ $subCount }}</span>
                                                    @endif
                                                </div>
                                                @if($subCount > 0)
                                                    <i class="fas fa-chevron-right hierarchy-arrow"></i>
                                                @endif
                                            </a>
                                            @php
                                                $subDepartments = $dept->visibleSubDepartments ?? $dept->subDepartments ?? collect();
                                            @endphp
                                            @if($subDepartments->count())
                                                <ul class="hierarchy-submenu">
                                                    @foreach($subDepartments as $sub)
                                                        <li class="hierarchy-sub-item" data-search-text="{{ strtolower($sub->name) }}">
                                                            <a href="#" class="hierarchy-link hierarchy-subdept-link"
                                                               wire:click.prevent="selectHierarchy('subdepartment', {{ $sub->id }})">
                                                                <div class="hierarchy-link-content">
                                                                    <i class="fas fa-layer-group hierarchy-icon"></i>
                                                                    <span class="hierarchy-name">{{ $sub->name }}</span>
                                                                    @php
                                                                        $services = $sub->visibleServices ?? $sub->services ?? collect();
                                                                        $srvCount = $services->count();
                                                                    @endphp
                                                                    @if($srvCount > 0)
                                                                        <span class="hierarchy-count">{{ $srvCount }}</span>
                                                                    @endif
                                                                </div>
                                                                @if($srvCount > 0)
                                                                    <i class="fas fa-chevron-right hierarchy-arrow"></i>
                                                                @endif
                                                            </a>
                                                            @php
                                                                $services = $sub->visibleServices ?? $sub->services ?? collect();
                                                            @endphp
                                                            @if($services->count())
                                                                <ul class="hierarchy-submenu">
                                                                    @foreach($services as $srv)
                                                                        <li class="hierarchy-service-item" data-search-text="{{ strtolower($srv->name) }}">
                                                                            <a href="#" class="hierarchy-link hierarchy-service-link"
                                                                               wire:click.prevent="selectHierarchy('service', {{ $srv->id }})">
                                                                                <div class="hierarchy-link-content">
                                                                                    <i class="fas fa-briefcase hierarchy-icon"></i>
                                                                                    <span class="hierarchy-name">{{ $srv->name }}</span>
                                                                                </div>
                                                                            </a>
                                                                        </li>
                                                                    @endforeach
                                                                </ul>
                                                            @endif
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </li>
                                                    @endforeach
                                </ul>
                                <div class="hierarchy-no-results" id="hierarchyNoResults" style="display: none;">
                                    <i class="fas fa-search mb-2"></i>
                                    <p class="mb-0">No matching items found</p>
                                </div>
                            </div>
                            <!-- Clear Filter Button (Footer) -->
                            <button class="hierarchy-clear-all" type="button" wire:click="clearHierarchy">
                                <span>Clear Filter</span>
                            </button>
                        </div>
                    </div>
                @endunless
                <div class="d-flex align-items-center gap-1">
                    <label>{{ ui_t('filters.from') }}: </label>
                    <input type="date" class="form-control" wire:model.change="dateFrom" placeholder="{{ ui_t('filters.from') }}" />
                </div>

                <div class="d-flex align-items-center gap-1">
                    <label>{{ ui_t('filters.to') }}: </label>
                    <input type="date" class="form-control" wire:model.change="dateTo" placeholder="{{ ui_t('filters.to') }}" />
                </div>

                <style>
                    .fav-toggle-input { display: none; }
                    .fav-toggle {
                        border: 1px solid #f59e0b;
                        color: #f59e0b;
                        background: #fff;
                        border-radius: 9999px;
                        padding: 6px 12px;
                        display: inline-flex;
                        align-items: center;
                        gap: 8px;
                        font-weight: 600;
                        transition: all .2s ease;
                        cursor: pointer;
                        user-select: none;
                    }
                    .fav-toggle:hover { box-shadow: 0 2px 8px rgba(245, 158, 11, 0.25); transform: translateY(-1px); }
                    .fav-toggle.is-active { background: #f59e0b; color: #1f2937; border-color: #f59e0b; }
                    .fav-toggle .fa-star { color: currentColor; }

                    /* ==================== Hierarchy Dropdown Styles (simple, flat UI) ==================== */
                    
                    /* Trigger Button */
                    .hierarchy-trigger-btn {
                        background: #ffffff;
                        color: #111827;
                        border: 1px solid #e5e7eb;
                        border-radius: 6px;
                        padding: 8px 14px;
                        font-size: 0.9rem;
                        font-weight: 500;
                        display: inline-flex;
                        align-items: center;
                        cursor: pointer;
                        transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
                        box-shadow: none;
                        min-width: 200px;
                    }
                    
                    .hierarchy-trigger-btn:hover {
                        background: #f3f4f6;
                        border-color: #d1d5db;
                    }
                    
                    .hierarchy-trigger-btn:active {
                        background: #e5e7eb;
                    }
                    
                    .hierarchy-selected-text {
                        flex: 1;
                        text-align: left;
                        white-space: nowrap;
                        overflow: hidden;
                        text-overflow: ellipsis;
                        max-width: 350px;
                    }
                    
                    .hierarchy-chevron {
                        font-size: 0.75rem;
                        transition: transform 0.3s ease;
                    }
                    
                    .hierarchy-trigger-btn[aria-expanded="true"] .hierarchy-chevron {
                        transform: rotate(180deg);
                    }
                    
                    /* Dropdown Menu */
                    /* Dropdown Menu */
                    .hierarchy-dropdown .dropdown-menu.hierarchy-menu {
                        min-width: 320px;
                        max-width: 360px;
                        padding: 0;
                        border: 1px solid rgba(0,0,0,0.08);
                        border-radius: 12px;
                        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
                        overflow: hidden;
                        margin-top: 8px;
                        background: #ffffff;
                    }
                    
                    /* Search Wrapper */
                    .hierarchy-search-wrapper {
                        position: relative;
                        padding: 12px;
                        background: #fff;
                        border-bottom: 1px solid #f1f5f9;
                    }
                    
                    .hierarchy-search-icon {
                        position: absolute;
                        left: 24px;
                        top: 50%;
                        transform: translateY(-50%);
                        color: #94a3b8;
                        font-size: 0.9rem;
                        pointer-events: none;
                    }
                    
                    .hierarchy-search-input {
                        width: 100%;
                        padding: 8px 36px 8px 36px;
                        border: 1px solid #e2e8f0;
                        border-radius: 8px;
                        font-size: 0.85rem;
                        transition: all 0.2s ease;
                        background: #f8fafc;
                        color: #334155;
                    }
                    
                    .hierarchy-search-input:focus {
                        outline: none;
                        border-color: #cbd5e1;
                        background: #fff;
                        box-shadow: 0 0 0 3px rgba(148, 163, 184, 0.1);
                    }
                    
                    .hierarchy-search-clear {
                        position: absolute;
                        right: 24px;
                        top: 50%;
                        transform: translateY(-50%);
                        background: none;
                        border: none;
                        color: #94a3b8;
                        cursor: pointer;
                        padding: 4px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        transition: color 0.2s ease;
                    }
                    
                    .hierarchy-search-clear:hover {
                        color: #ef4444;
                    }
                    
                    /* Clear All Link */
                    .hierarchy-clear-all {
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        width: 100%;
                        background: #f8fafc;
                        border: none;
                        border-top: 1px solid #f1f5f9;
                        padding: 10px;
                        color: #64748b;
                        font-size: 0.8rem;
                        font-weight: 500;
                        cursor: pointer;
                        transition: all 0.2s ease;
                    }
                    
                    .hierarchy-clear-all:hover {
                        background: #f1f5f9;
                        color: #ef4444;
                    }

                    .hierarchy-divider {
                        display: none;
                    }
                    
                    /* Scroll Container */
                    .hierarchy-scroll-container {
                        /* Allow submenus to extend outside without being clipped */
                        max-height: none;
                        overflow: visible;
                        position: relative;
                    }
                    
                    /* Custom Scrollbar */
                    .hierarchy-scroll-container::-webkit-scrollbar {
                        width: 6px;
                    }
                    
                    .hierarchy-scroll-container::-webkit-scrollbar-track {
                        background: #f8f9fa;
                    }
                    
                    .hierarchy-scroll-container::-webkit-scrollbar-thumb {
                        background: #cbd5e0;
                        border-radius: 3px;
                    }
                    
                    .hierarchy-scroll-container::-webkit-scrollbar-thumb:hover {
                        background: #a0aec0;
                    }
                    
                    /* Hierarchy List */
                    .hierarchy-list {
                        list-style: none;
                        padding: 8px 0;
                        margin: 0;
                    }
                    
                    .hierarchy-item,
                    .hierarchy-sub-item,
                    .hierarchy-service-item {
                        position: relative;
                        list-style: none;
                    }
                    
                    /* Hierarchy Links */
                    .hierarchy-link {
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        padding: 8px 12px;
                        color: #1f2937;
                        text-decoration: none;
                        transition: all 0.15s ease;
                        cursor: pointer;
                        position: relative;
                        border-radius: 4px;
                        margin: 0 4px;
                    }
                    
                    .hierarchy-link:hover {
                        background: #f3f4f6;
                        color: #667eea;
                        text-decoration: none;
                    }
                    
                    .hierarchy-link-content {
                        display: flex;
                        align-items: center;
                        gap: 10px;
                        flex: 1;
                        min-width: 0;
                    }
                    
                    .hierarchy-icon {
                        font-size: 0.875rem;
                        width: 18px;
                        flex-shrink: 0;
                    }
                    
                    .hierarchy-dept-link .hierarchy-icon {
                        color: #667eea;
                    }
                    
                    .hierarchy-subdept-link .hierarchy-icon {
                        color: #764ba2;
                    }
                    
                    .hierarchy-service-link .hierarchy-icon {
                        color: #f093fb;
                    }
                    
                    .hierarchy-name {
                        flex: 1;
                        font-size: 0.875rem;
                        white-space: nowrap;
                        overflow: hidden;
                        text-overflow: ellipsis;
                        font-weight: 400;
                    }
                    
                    .hierarchy-count {
                        background: #667eea;
                        color: white;
                        font-size: 0.7rem;
                        padding: 2px 6px;
                        border-radius: 10px;
                        font-weight: 600;
                        flex-shrink: 0;
                        min-width: 20px;
                        text-align: center;
                    }
                    
                    .hierarchy-arrow {
                        font-size: 0.7rem;
                        color: #9ca3af;
                        margin-left: 6px;
                        flex-shrink: 0;
                        transition: transform 0.15s ease, color 0.15s ease;
                    }
                    
                    .hierarchy-link:hover .hierarchy-arrow {
                        transform: translateX(2px);
                        color: #667eea;
                    }
                    
                    /* Submenus - Flyout style */
                    .hierarchy-submenu {
                        display: none;
                        position: absolute;
                        top: 0;
                        left: 100%;
                        width: 250px;
                        max-width: 250px;
                        max-height: 380px;
                        overflow-y: auto;
                        overflow-x: hidden;
                        background: #ffffff;
                        border: 1px solid #e5e7eb;
                        border-radius: 8px;
                        box-shadow: 0 6px 18px rgba(15, 23, 42, 0.12);
                        z-index: 1070;
                        list-style: none;
                        padding: 8px 0;
                    }
                    
                    /* Custom scrollbar for submenus */
                    .hierarchy-submenu::-webkit-scrollbar {
                        width: 5px;
                    }
                    
                    .hierarchy-submenu::-webkit-scrollbar-track {
                        background: #f9fafb;
                    }
                    
                    .hierarchy-submenu::-webkit-scrollbar-thumb {
                        background: #d1d5db;
                        border-radius: 3px;
                    }
                    
                    .hierarchy-submenu::-webkit-scrollbar-thumb:hover {
                        background: #9ca3af;
                    }
                    
                    .hierarchy-item:hover > .hierarchy-submenu,
                    .hierarchy-sub-item:hover > .hierarchy-submenu {
                        display: block;
                    }

                    /* Search suggestions under search bar */
                    .search-files.position-relative {
                        position: relative;
                    }
                    .search-suggestions {
                        position: absolute;
                        top: 100%;
                        left: 0;
                        right: 0;
                        z-index: 1100;
                        margin-top: 4px;
                        background: #fff;
                        border: 1px solid #e5e7eb;
                        border-radius: 6px;
                        box-shadow: 0 8px 16px rgba(15, 23, 42, 0.12);
                        max-height: 260px;
                        overflow-y: auto;
                    }
                    .search-suggestion-item {
                        border: none;
                        background: transparent;
                        padding: 6px 10px;
                        font-size: 0.875rem;
                        cursor: pointer;
                    }
                    .search-suggestion-item:hover {
                        background: #f3f4f6;
                    }
                    
                    /* No Results Message */
                    .hierarchy-no-results {
                        text-align: center;
                        padding: 40px 20px;
                        color: #6c757d;
                    }
                    
                    .hierarchy-no-results i {
                        font-size: 2.5rem;
                        color: #cbd5e0;
                    }
                    
                    .hierarchy-no-results p {
                        font-size: 0.9rem;
                        margin-top: 8px;
                    }
                    
                    /* Hide/Show utility for search filtering */
                    .hierarchy-item.hidden,
                    .hierarchy-sub-item.hidden,
                    .hierarchy-service-item.hidden {
                        display: none;
                    }
                    
                    /* Highlight search matches */
                    .hierarchy-link mark {
                        background: #fef3c7;
                        color: #92400e;
                        padding: 2px 4px;
                        border-radius: 3px;
                        font-weight: 600;
                    }
                    
                    /* Responsive adjustments */
                    @media (max-width: 768px) {
                        .hierarchy-dropdown .dropdown-menu.hierarchy-menu {
                            min-width: 320px;
                            max-width: 90vw;
                        }
                        
                        .hierarchy-submenu {
                            position: static;
                            box-shadow: none;
                            border-left: 3px solid #667eea;
                            margin-left: 20px;
                            margin-top: 4px;
                            border-radius: 0;
                        }
                        
                        .hierarchy-item:hover > .hierarchy-submenu,
                        .hierarchy-sub-item:hover > .hierarchy-submenu {
                            display: none;
                        }
                    }
                </style>
                <div class="fav-toggle-wrap ms-2 d-flex align-items-center">
                    <input type="checkbox" id="favoritesOnly" class="fav-toggle-input" wire:model.change="favoritesOnly">
                    <label for="favoritesOnly"
                           class="fav-toggle {{ $favoritesOnly ? 'is-active' : '' }}"
                           aria-pressed="{{ $favoritesOnly ? 'true' : 'false' }}"
                           role="button"
                           title="{{ ui_t('filters.favorites_only') }}">
                        <i class="{{ $favoritesOnly ? 'fa-solid' : 'fa-regular' }} fa-star"></i>
                        <span class="d-none d-md-inline">{{ ui_t('filters.favorites_only') }}</span>
                    </label>
                </div>

                <!-- Reset button -->
                <button type="button" wire:click="resetFilters" class="btn btn-sm btn-outline-danger text-nowrap">
                    {{ ui_t('filters.reset_filters') }}
                </button>

                <!-- Per-page selector -->
                <div class="ms-2">
                    <select class="form-select" wire:model.change="perPage" style="min-width: 110px;">
                        <option value="10">10 per page</option>
                        <option value="25">25 per page</option>
                        <option value="50">50 per page</option>
                    </select>
                </div>

            </div>
        </div>

        <table class="files-table">
            <thead>
            <tr>
                <th>
                    <input
                        type="checkbox"
                        wire:click="toggleSelectAll"
                        @checked($selectAll)
                    >
                </th>
                <th>{{ ui_t('tables.file_name') }}</th>
                {{-- @unless($this->showOnlyPendingApprovals)   
                    <th>{{ ui_t('tables.last_version') }}</th>
                @endunless --}}
                <th>{{ ui_t('tables.structure') }}</th>
                <th>{{ ui_t('tables.created_by') }}</th>
                <th>{{ ui_t('tables.created_at') }}</th>
                <th>{{ ui_t('tables.expire_at') ?? 'Expire at' }}</th>
                <th>{{ ui_t('tables.status') }}</th>
                <th>{{ ui_t('tables.physical_location') }}</th>
                <th>{{ ui_t('tables.actions') }}</th>
            </tr>
            </thead>
            <tbody>
            {{-- Folders first (hidden on dashboard pending-approvals view and File Audit page) --}}
            @unless($this->showOnlyPendingApprovals || request()->routeIs('documents.index'))
                @foreach($folders as $folder)
                    <tr class="folder-row">
                        <td>
                            {{-- Placeholder checkbox; can be wired for bulk actions later --}}
                            <input type="checkbox" disabled>
                        </td>
                        <td>
                            <div class="file-item">
                                <div class="file-icon">
                                    <i class="fa-solid fa-folder" style="font-size: 24px; color: #fbbf24;"></i>
                                </div>
                                <div>
                                    <div class="file-name text-truncate" style="max-width: 260px;" title="{{ $folder->name }}">
                                        <a href="#" wire:click.prevent="openFolder({{ $folder->id }})">
                                            {{ $folder->name }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div>{{ $folder->department?->name }}</div>
                        </td>
                        <td>
                            <div>{{ $folder->creator?->full_name }}</div>
                        </td>
                        <td>
                            <div class="file-date">{{ $folder->created_at?->format('d/m/Y') }}<br/>{{ $folder->created_at?->format('H:i') }}</div>
                        </td>
                        <td>
                            {{-- Folders do not have expiration dates --}}
                            <span class="text-muted">—</span>
                        </td>
                        <td>
                            <button class="status-badge border-0">
                                {{ $folder->status }}
                            </button>
                        </td>
                        <td>
                            <span class="text-muted">—</span>
                        </td>
                        <td>
                            <div class="file-actions d-flex justify-content-center">
                                @can('approve', \App\Models\Document::class)
                                    <a class="btn-table btn-table-approve trigger-action"
                                       data-id="{{ $folder->id }}"
                                       data-name="{{ $folder->name }}"
                                       data-url="{{ route('folders.approve', $folder) }}"
                                       data-method="PUT"
                                       data-button-text="{{ ui_t('actions.confirm') }}"
                                       data-title="{{ ui_t('pages.documents.approve_title') }}"
                                       data-body="{{ ui_t('pages.documents.approve_body') }}"
                                       title="{{ ui_t('actions.approve') }}"
                                       aria-label="{{ ui_t('actions.approve') }}">
                                        <i class="fa-solid fa-check"></i>
                                    </a>
                                @endcan
                                @can('decline', \App\Models\Document::class)
                                    <a class="btn-table btn-table-reject trigger-action ms-1"
                                       data-id="{{ $folder->id }}"
                                       data-name="{{ $folder->name }}"
                                       data-url="{{ route('folders.decline', $folder) }}"
                                       data-method="PUT"
                                       data-button-text="{{ ui_t('actions.confirm') }}"
                                       data-title="{{ ui_t('pages.documents.reject_title') }}"
                                       data-body="{{ ui_t('pages.documents.reject_body') }}"
                                       title="{{ ui_t('actions.reject') }}"
                                       aria-label="{{ ui_t('actions.reject') }}">
                                        <i class="fa-solid fa-xmark"></i>
                                    </a>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforeach
            @endunless

            {{-- Documents --}}
            @foreach($documents as $doc)
                <tr class="document-row" data-doc-id="{{ $doc->id }}">
                    <td>
                        <input type="checkbox"
                               wire:model.change="checkedDocuments"
                               value="{{ $doc->id }}">
                    </td>
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
                                <div class="file-name text-truncate" style="max-width: 260px;" title="{{ $doc->title }}">{{ $doc->title }}</div>
                            </div>
                        </div>
                    </td>
                    {{-- @unless($this->showOnlyPendingApprovals)
                        <td>
                            <div>{{ $doc->latestVersion?->version_number }}</div>
                        </td>
                    @endunless --}}
                    <td>
                        <div>{{ $doc->department?->name }}</div>
                    </td>
                    <td>
                        <div>{{ $doc->createdBy?->full_name }}</div>
                    </td>


                    <td>
                        <div class="file-date">{{ $doc->created_at->format('d/m/Y') }}
                            <br/>{{ $doc->created_at->format('H:i') }}</div>
                    </td>

                    <td>
                        <div class="file-date">
                            @if($doc->expire_at)
                                {{ $doc->expire_at->format('d/m/Y') }}
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </div>
                    </td>

                    <td>
                        @php
                            $isExpired = $doc->expire_at && $doc->expire_at->isPast();
                        @endphp
                        <div class="d-flex flex-column gap-1">
                            <button class="status-badge 
                                @if($doc->status === 'approved') approved
                                @elseif($doc->status === 'pending') pending
                                @elseif($doc->status === 'declined') declined
                                @elseif($doc->status === 'archived') archived
                                @else approved
                                @endif border-0">
                                {{ ui_t('pages.documents.status.' . $doc->status) }}
                                @if($doc->status === 'declined' && $doc->created_by === auth()->id())
                                    <i class="fas fa-trash-can ms-1" title="{{ ui_t('pages.documents.permanent_delete_hint') }}"></i>
                                @endif
                            </button>
                            @if($isExpired)
                                <span class="badge bg-danger" style="font-size: 0.7rem;">{{ ui_t('pages.documents.status.expired') }}</span>
                            @endif
                        </div>
                    </td>
                    <td>
                        <div>
                            @if($doc->box)
                                <span class="text-muted small">{{ $doc->box->__toString() }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </div>
                    </td>
                    <td>
                        <div class="file-actions d-flex justify-content-center">
                            @if($this->showOnlyPendingApprovals)
                                {{-- Approvals view: only approve, decline, preview --}}
                                @if($doc->status === 'pending')
                                    @can('decline', \App\Models\Document::class)
                                        <button
                                            class="btn-table btn-table-reject trigger-action"
                                            data-id="{{ $doc->id }}"
                                            data-name="{{ $doc->title }}"
                                            data-url="{{ route('documents.decline', $doc->id) }}"
                                            data-method="PUT"
                                            data-button-text="{{ ui_t('actions.confirm') }}"
                                            data-title="{{ ui_t('pages.documents.reject_title') }}"
                                            data-body="{{ ui_t('pages.documents.reject_body') }}"
                                            title="{{ ui_t('actions.reject') }}"
                                            aria-label="{{ ui_t('actions.reject') }}"
                                        >
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    @endcan
                                    @can('approve', \App\Models\Document::class)
                                        <button
                                            class="btn-table btn-table-approve trigger-action"
                                            data-id="{{ $doc->id }}"
                                            data-name="{{ $doc->title }}"
                                            data-url="{{ route('documents.approve', $doc->id) }}"
                                            data-method="PUT"
                                            data-button-text="{{ ui_t('actions.confirm') }}"
                                            data-title="{{ ui_t('pages.documents.approve_title') }}"
                                            data-body="{{ ui_t('pages.documents.approve_body') }}"
                                            data-button-class="btn-table-approve"
                                            title="{{ ui_t('actions.approve') }}"
                                            aria-label="{{ ui_t('actions.approve') }}"
                                        >
                                            <i class="fa-solid fa-check"></i>
                                        </button>
                                    @endcan
                                @endif

                                @can('view',$doc)
                                    @if($doc->latestVersion)
                                        @php
                                            // In approvals view, use the rich preview with metadata sidebar
                                            $previewParams = ['id' => $doc->latestVersion->id, 'approval' => 1];
                                        @endphp
                                        <a href="{{ route('document-versions.preview', $previewParams) }}"
                                           class="btn-table btn-table-preview ms-1" title="{{ ui_t('actions.preview') }}" aria-label="{{ ui_t('actions.preview') }}">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                    @endif
                                @endcan
                            @else
                                {{-- Normal documents view: full action set --}}
                                @php
                                    $isFavorite = auth()->check() && auth()->user()->favoriteDocuments()->where('document_id', $doc->id)->exists();
                                @endphp
                                <button type="button"
                                        class="btn-table btn-table-favorite me-1"
                                        title="{{ $isFavorite ? ui_t('pages.documents.favorite.remove') : ui_t('pages.documents.favorite.add') }}"
                                        aria-label="{{ ui_t('pages.documents.favorite.label') }}"
                                        wire:click="toggleFavorite({{ $doc->id }})">
                                    @if($isFavorite)
                                        <i class="fa-solid fa-star" style="color:#f59e0b"></i>
                                    @else
                                        <i class="fa-regular fa-star"></i>
                                    @endif
                                </button>

                                @can('view',$doc)
                                    @if($doc->latestVersion)
                                        @php
                                            $previewParams = ['id' => $doc->latestVersion->id];
                                        @endphp
                                        <a href="{{ route('document-versions.fullscreen', $previewParams) }}"
                                           class="btn-table btn-table-preview" title="{{ ui_t('actions.preview') }}" aria-label="{{ ui_t('actions.preview') }}">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                    @endif
                                @endcan
                                @can('viewAny', \App\Models\User::class)
                                <button type="button" class="btn-table btn-table-logs toggle-log ms-1" data-doc-id="{{ $doc->id }}" title="{{ ui_t('pages.documents.show_log') }}" aria-label="{{ ui_t('pages.documents.show_log') }}">
                                    <i class="fa-solid fa-clock-rotate-left"></i>
                                </button>
                                @endcan
                                <div class="dropdown">
                                    <button class="btn text-black" style="background: #e6e6e6" type="button"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                        ⋮
                                    </button>
                                    <ul class="dropdown-menu">
                                        @can('update',$doc)
                                        @php
                                            $renameFields = [[
                                                'type' => 'text',
                                                'name' => 'title',
                                                'placeholder' => ui_t('pages.documents.rename_placeholder'),
                                            ]];
                                        @endphp
                                        <li class="pointer">
                                            <a class="dropdown-item trigger-action "
                                               data-id="{{ $doc->id }}"
                                               data-name="{{ $doc->title }}"
                                               data-url="{{ route('documents.rename', $doc->id) }}"
                                               data-method="PUT"
                                               data-button-text="{{ ui_t('pages.documents.rename_button') }}"
                                               data-button-class="btn-dark"
                                               data-title="{{ ui_t('pages.documents.rename_title') }}"
                                               data-body="{{ ui_t('pages.documents.rename_body') }}"
                                               data-extra-fields='@json($renameFields)'
                                            >
                                                <i class="fa-solid fa-pen-to-square"></i> {{ ui_t('pages.documents.rename_button') }}
                                            </a>
                                        </li>
                                        @endcan


                                        @if(auth()->user()?->hasRole('master') || auth()->user()?->hasRole('Super Administrator'))
                                            <li class="pointer">
                                                <a class="dropdown-item trigger-action"
                                                   data-id="{{ $doc->id }}"
                                                   data-name="{{ $doc->title }}"
                                                   data-url="{{ route('documents.permanent-delete', $doc->id) }}"
                                                   data-method="DELETE"
                                                   data-button-text="{{ ui_t('pages.actions.delete_permanently') }}"
                                                   data-button-class="btn-danger"
                                                   data-title="{{ ui_t('pages.documents.permanent_delete_title') }}"
                                                   data-body="{{ ui_t('pages.documents.permanent_delete_body') }}">
                                                    <i class="fa-solid fa-trash"></i> {{ ui_t('pages.actions.delete_permanently') }}
                                                </a>
                                            </li>
                                        @elseif($doc->status === 'declined' && $doc->created_by === auth()->id())
                                            {{-- Allow service users to permanently delete their own declined documents --}}
                                            <li class="pointer">
                                                <a class="dropdown-item trigger-action text-danger"
                                                   data-id="{{ $doc->id }}"
                                                   data-name="{{ $doc->title }}"
                                                   data-url="{{ route('documents.permanent-delete', $doc->id) }}"
                                                   data-method="DELETE"
                                                   data-button-text="{{ ui_t('pages.actions.delete_permanently') }}"
                                                   data-button-class="btn-danger"
                                                   data-title="{{ ui_t('pages.documents.permanent_delete_title') }}"
                                                   data-body="{{ ui_t('pages.documents.permanent_delete_body') }}">
                                                    <i class="fa-solid fa-trash"></i> {{ ui_t('pages.actions.delete_permanently') }}
                                                </a>
                                            </li>
                                        @endif


                                            @can('view',$doc)
                                        <li class="pointer">
                                            <a class="dropdown-item" href="#" 
                                               onclick="event.preventDefault(); showDocumentMetadata({{ $doc->id }})">
                                                <i class="fa-solid fa-info-circle"></i> {{ ui_t('pages.upload.show_metadata') }}
                                            </a>
                                        </li>
                                        <li class="pointer"><a class="dropdown-item"
                                                               href="{{ route('documents.download',['id' => $doc->id]) }}"><i
                                                    class="fa-solid fa-download"></i> {{ ui_t('pages.documents.download') }}</a>
                                        </li>
                                            @endcan
                                        {{--
                                                                            <li><a class="dropdown-item" href="#"><i class="fa-regular fa-star"></i> Add Favorite</a></li>
                                        --}}
                                    </ul>

                                </div>
                            @endif
                        </div>
                    </td>
                </tr>
                @can('viewAny', \App\Models\User::class)
                <tr class="log-row d-none" data-doc-id="{{ $doc->id }}">
                <td colspan="8">
                        @foreach($doc->auditLogs as $log)
                            @if($log->action !== 'viewed_ocr')
                            <div class="px-5 pt-2">
                                <div class="activity-step">
                                    <div class="icon-box bord-color">
                                        <i class="fas fa-check fa-2xl"></i>
                                    </div>
                                    <div class="ms-4">
                                        <strong>{{ $log->action }}</strong>
                                        <div class="activity-meta">
                                            <i class="fa-solid fa-clock fa-sm me-2"></i>{{ optional($log->occurred_at)->format('Y-m-d H:i') }}
                                        </div>
                                        <div class="activity-user">
                                            @if($log->user)
                                                @php
                                                    $imagePath = $log->user->avatar_url ?? asset('assets/user.png');
                                                @endphp
                                                <img
                                                    src="{{ $imagePath }}"
                                                    alt="{{ $log->user->full_name }}"
                                                    class="rounded-circle"
                                                    style="width: 40px; height: 40px; object-fit: cover;"
                                                    onerror="this.onerror=null;this.src='{{ asset('assets/user.png') }}';"
                                                >
                                            @else
                                                <div class="bg-secondary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                    <i class="fas fa-user text-secondary"></i>
                                                </div>
                                            @endif
                                            <div>
                                                <div>{{ $log->user->full_name ?? ui_t('pages.activity.table.na') }}</div>
                                                <div class="activity-role">{{ $log->user->role ?? ui_t('pages.activity.table.na') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif
                        @endforeach

                    </td>
                </tr>
                @endcan
                @include('components.modals.move-documents-modal',['document' => $doc,'rooms' => $rooms])
                @include('components.modals.document-destruction-modal',['document' => $doc,'movements' => $movements])
            @endforeach


            </tbody>
        </table>




        <!-- Document Metadata Modal -->
        <div class="modal fade" id="documentMetadataModal" tabindex="-1" aria-labelledby="documentMetadataLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="documentMetadataLabel">
                            <i class="fa-solid fa-info-circle me-2"></i>
                            {{ ui_t('pages.upload.document_information') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="metadataContent">
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ ui_t('actions.close') ?? 'Close' }}</button>
                        <button type="button" class="btn btn-primary" id="metadataSaveBtn" disabled>{{ __('Save') }}</button>
                    </div>
                </div>
            </div>
        </div>

        @include('components.modals.confirm-modal')
        <x-pagination :items="$documents"></x-pagination>
    </div>

</div>


<script>
    // ==================== GLOBAL DEFINITIONS (must be outside DOMContentLoaded) ====================
    
    // Translation strings passed from PHP for metadata modal - GLOBAL
    window.TRANSLATIONS = {
        documentInformation: '{{ ui_t("pages.upload.document_information") }}',
        organizationalStructure: '{{ ui_t("pages.upload.organizational_structure") }}',
        physicalLocation: '{{ ui_t("pages.upload.physical_location") }}',
        tags: '{{ ui_t("pages.upload.tags") }}',
        documentTitle: '{{ ui_t("pages.upload.document_title") }}',
        status: '{{ ui_t("filters.status") }}',
        createdAt: '{{ ui_t("pages.upload.creation_date") }}',
        expiresAt: '{{ ui_t("pages.upload.expire_date") }}',
        createdBy: '{{ ui_t("pages.upload.created_by") }}',
        fileType: '{{ ui_t("pages.upload.file_type") }}',
        pole: '{{ ui_t("pages.upload.pole") }}',
        department: '{{ ui_t("pages.upload.sub_department") }}',
        service: '{{ ui_t("pages.upload.service") }}',
        category: '{{ ui_t("pages.upload.category") }}',
        room: '{{ ui_t("pages.upload.room") }}',
        row: '{{ ui_t("pages.upload.row") }}',
        shelf: '{{ ui_t("pages.upload.shelf") }}',
        box: '{{ ui_t("pages.upload.box") }}',
        tagsCommaSeparated: '{{ ui_t("pages.upload.tags_comma_separated") }}',
        enterTagsComma: '{{ ui_t("pages.upload.enter_tags_comma") }}',
        selectRoomFirst: '{{ ui_t("pages.upload.select_room_first") }}',
        selectRowFirst: '{{ ui_t("pages.upload.select_row_first") }}',
        selectShelfFirst: '{{ ui_t("pages.upload.select_shelf_first") }}',
        selectBoxFirst: '{{ ui_t("pages.upload.select_box_first") }}',
    };

    // Toast notification function - GLOBAL
    window.showToast = function(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `alert alert-${type} position-fixed top-0 start-50 translate-middle-x mt-3 shadow-lg`;
        toast.style.cssText = 'z-index: 9999; min-width: 300px;';
        toast.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="fas fa-check-circle me-2"></i>
                <span>${message}</span>
            </div>
        `;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.transition = 'opacity 0.3s';
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    };

    // CSRF Token for AJAX requests
    const CSRF_TOKEN = '{{ csrf_token() }}';
    let currentMetadata = null;

    document.addEventListener('DOMContentLoaded', function () {
        // ==================== Hierarchy Dropdown Search Functionality ====================
        const searchInput = document.getElementById('hierarchySearchInput');
        const clearSearchBtn = document.getElementById('hierarchyClearSearch');
        const hierarchyList = document.getElementById('hierarchyList');
        const noResults = document.getElementById('hierarchyNoResults');
        
        if (searchInput && hierarchyList) {
            // Search functionality
            searchInput.addEventListener('input', function(e) {
                const searchTerm = e.target.value.toLowerCase().trim();
                
                // Show/hide clear button
                if (clearSearchBtn) {
                    clearSearchBtn.style.display = searchTerm ? 'flex' : 'none';
                }
                
                if (searchTerm === '') {
                    // Reset all items to visible
                    resetSearch();
                    return;
                }
                
                let hasVisibleItems = false;
                
                // Search through all hierarchy items
                const allItems = hierarchyList.querySelectorAll('.hierarchy-item, .hierarchy-sub-item, .hierarchy-service-item');
                
                allItems.forEach(item => {
                    const searchText = item.getAttribute('data-search-text') || '';
                    const matchesSearch = searchText.includes(searchTerm);
                    
                    if (matchesSearch) {
                        // Show matching item
                        item.classList.remove('hidden');
                        hasVisibleItems = true;
                        
                        // Also show all parent items
                        let parent = item.parentElement;
                        while (parent && parent.id !== 'hierarchyList') {
                            if (parent.classList.contains('hierarchy-item') || 
                                parent.classList.contains('hierarchy-sub-item')) {
                                parent.classList.remove('hidden');
                            }
                            parent = parent.parentElement;
                        }
                        
                        // Highlight matching text
                        const nameSpan = item.querySelector('.hierarchy-name');
                        if (nameSpan) {
                            const originalText = nameSpan.textContent;
                            const regex = new RegExp(`(${searchTerm})`, 'gi');
                            nameSpan.innerHTML = originalText.replace(regex, '<mark>$1</mark>');
                        }
                    } else {
                        // Hide non-matching items
                        item.classList.add('hidden');
                        
                        // Remove any highlighting
                        const nameSpan = item.querySelector('.hierarchy-name');
                        if (nameSpan && nameSpan.querySelector('mark')) {
                            nameSpan.innerHTML = nameSpan.textContent;
                        }
                    }
                });
                
                // Show/hide no results message
                if (noResults) {
                    noResults.style.display = hasVisibleItems ? 'none' : 'block';
                }
                if (hierarchyList) {
                    hierarchyList.style.display = hasVisibleItems ? 'block' : 'none';
                }
            });
            
            // Clear search button
            if (clearSearchBtn) {
                clearSearchBtn.addEventListener('click', function() {
                    searchInput.value = '';
                    searchInput.dispatchEvent(new Event('input'));
                    searchInput.focus();
                });
            }
            
            // Reset search function
            function resetSearch() {
                // Show all items
                const allItems = hierarchyList.querySelectorAll('.hierarchy-item, .hierarchy-sub-item, .hierarchy-service-item');
                allItems.forEach(item => {
                    item.classList.remove('hidden');
                    
                    // Remove highlighting
                    const nameSpan = item.querySelector('.hierarchy-name');
                    if (nameSpan && nameSpan.querySelector('mark')) {
                        nameSpan.innerHTML = nameSpan.textContent;
                    }
                });
                
                // Hide no results message
                if (noResults) {
                    noResults.style.display = 'none';
                }
                if (hierarchyList) {
                    hierarchyList.style.display = 'block';
                }
            }
            
            // Clear search when dropdown is closed
            const dropdownElement = document.querySelector('.hierarchy-dropdown');
            if (dropdownElement) {
                dropdownElement.addEventListener('hidden.bs.dropdown', function() {
                    searchInput.value = '';
                    resetSearch();
                    if (clearSearchBtn) {
                        clearSearchBtn.style.display = 'none';
                    }
                });
            }
        }
        
        // ==================== Document Log Toggle Functionality ====================
        const toggleButtons = document.querySelectorAll('.toggle-log');

        toggleButtons.forEach(button => {
            button.addEventListener('click', function () {
                const docId = this.dataset.docId;
                const logRow = document.querySelector(`.log-row[data-doc-id="${docId}"]`);
                if (logRow) {
                    logRow.classList.toggle('d-none');
                }
            });
        });
    });
    
    // ==================== Show Document Metadata Function ====================
    window.showDocumentMetadata = function(documentId) {
        const metadataModal = new bootstrap.Modal(document.getElementById('documentMetadataModal'));
        const metadataContent = document.getElementById('metadataContent');
        const saveBtn = document.getElementById('metadataSaveBtn');

        metadataContent.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `;

        if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.onclick = null;
        }

        metadataModal.show();

        fetch(`/documents/${documentId}/metadata`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                currentMetadata = data.metadata;
                metadataContent.innerHTML = formatMetadataEditable(currentMetadata);
                attachMetadataFormListeners();
                if (saveBtn) {
                    saveBtn.onclick = () => submitMetadataChanges(currentMetadata.id);
                }
            } else {
                metadataContent.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        ${data.message || 'Failed to load metadata'}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error fetching metadata:', error);
            metadataContent.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    An error occurred while loading metadata
                </div>
            `;
        });
    }
    
    function formatMetadataEditable(metadata) {
        const expireValue = metadata.expire_at_raw || '';
        const departments = metadata.departments || [];
        const subDepartments = metadata.sub_departments || [];
        const services = metadata.services || [];
        const categories = metadata.categories || [];

        const deptOptions = departments.map(d => `
            <option value="${d.id}" ${String(d.id) == String(metadata.department_id ?? '') ? 'selected' : ''}>${d.name}</option>
        `).join('');

        const subDeptOptions = subDepartments.map(s => `
            <option value="${s.id}" data-department-id="${s.department_id}" ${String(s.id) == String(metadata.sub_department_id ?? '') ? 'selected' : ''}>${s.name}</option>
        `).join('');

        const serviceOptions = services.map(s => `
            <option value="${s.id}" data-sub-department-id="${s.sub_department_id}" ${String(s.id) == String(metadata.service_id ?? '') ? 'selected' : ''}>${s.name}</option>
        `).join('');

        const categoryOptions = categories.map(c => `
            <option value="${c.id}" data-service-id="${c.service_id}" ${String(c.id) == String(metadata.category_id ?? '') ? 'selected' : ''}>${c.name}</option>
        `).join('');

        return `
            <form id="metadataForm">
            <div class="metadata-container">
                <div class="metadata-section mb-4">
                    <h6 class="text-primary border-bottom pb-2 mb-3">
                        <i class="fas fa-file me-2"></i>${TRANSLATIONS.documentInformation}
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="text-muted small d-block">${TRANSLATIONS.documentTitle}</label>
                            <input type="text" class="form-control" value="${metadata.title || ''}" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small d-block">${TRANSLATIONS.status}</label>
                            <span class="badge bg-${getStatusColor(metadata.status)}">${metadata.status || 'N/A'}</span>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small d-block">${TRANSLATIONS.createdAt}</label>
                            <input type="text" class="form-control" value="${metadata.created_at || 'N/A'}" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small d-block">${TRANSLATIONS.expiresAt}</label>
                            <input type="date" class="form-control" value="${expireValue}" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small d-block">${TRANSLATIONS.createdBy}</label>
                            <input type="text" class="form-control" value="${metadata.created_by || 'N/A'}" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small d-block">${TRANSLATIONS.fileType}</label>
                            <input type="text" class="form-control" value="${metadata.file_type ? metadata.file_type.toUpperCase() : 'N/A'}" disabled>
                        </div>
                    </div>
                </div>

                <div class="metadata-section mb-4">
                    <h6 class="text-primary border-bottom pb-2 mb-3">
                        <i class="fas fa-sitemap me-2"></i>${TRANSLATIONS.organizationalStructure}
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="text-muted small d-block">${TRANSLATIONS.pole}</label>
                            <select class="form-select" disabled>
                                ${deptOptions}
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted small d-block">${TRANSLATIONS.department}</label>
                            <select class="form-select" disabled>
                                ${subDeptOptions}
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted small d-block">${TRANSLATIONS.service}</label>
                            <select class="form-select" disabled>
                                ${serviceOptions}
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="text-muted small d-block">${TRANSLATIONS.category}</label>
                            <select name="category_id" class="form-select">
                                ${categoryOptions}
                            </select>
                        </div>
                    </div>
                </div>

                <div class="metadata-section mb-4">
                    <h6 class="text-primary border-bottom pb-2 mb-3">
                        <i class="fas fa-map-marker-alt me-2"></i>${TRANSLATIONS.physicalLocation}
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="text-muted small d-block">${TRANSLATIONS.room}</label>
                            <select name="room_id" class="form-select" data-location-level="room">
                                <option value="">${TRANSLATIONS.selectRoomFirst}</option>
                                ${(metadata.rooms || []).map(room => `
                                    <option value="${room.id}" ${String(room.id) == String(metadata.room_id ?? '') ? 'selected' : ''}>${room.name}</option>
                                `).join('')}
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small d-block">${TRANSLATIONS.row}</label>
                            <select name="row_id" class="form-select" data-location-level="row" ${!metadata.room_id ? 'disabled' : ''}>
                                <option value="">${TRANSLATIONS.selectRowFirst}</option>
                                ${(metadata.rows || []).map(row => `
                                    <option value="${row.id}" data-room-id="${row.room_id}" ${String(row.id) == String(metadata.row_id ?? '') ? 'selected' : ''}>${row.name}</option>
                                `).join('')}
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small d-block">${TRANSLATIONS.shelf}</label>
                            <select name="shelf_id" class="form-select" data-location-level="shelf" ${!metadata.row_id ? 'disabled' : ''}>
                                <option value="">${TRANSLATIONS.selectShelfFirst}</option>
                                ${(metadata.shelves || []).map(shelf => `
                                    <option value="${shelf.id}" data-row-id="${shelf.row_id}" ${String(shelf.id) == String(metadata.shelf_id ?? '') ? 'selected' : ''}>${shelf.name}</option>
                                `).join('')}
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small d-block">${TRANSLATIONS.box}</label>
                            <select name="box_id" class="form-select" data-location-level="box" ${!metadata.shelf_id ? 'disabled' : ''}>
                                <option value="">${TRANSLATIONS.selectBoxFirst}</option>
                                ${(metadata.boxes || []).map(box => `
                                    <option value="${box.id}" data-shelf-id="${box.shelf_id}" ${String(box.id) == String(metadata.box_id ?? '') ? 'selected' : ''}>${box.name}</option>
                                `).join('')}
                            </select>
                        </div>
                    </div>
                </div>

                <div class="metadata-section mb-4">
                    <h6 class="text-primary border-bottom pb-2 mb-3">
                        <i class="fas fa-tags me-2"></i>${TRANSLATIONS.tags}
                    </h6>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="text-muted small d-block">${TRANSLATIONS.tagsCommaSeparated}</label>
                            <input type="text" name="tags" class="form-control" 
                                   value="${(metadata.tags || []).join(', ')}" 
                                   placeholder="${TRANSLATIONS.enterTagsComma}">
                            <small class="text-muted">${TRANSLATIONS.enterTagsComma}</small>
                        </div>
                    </div>
                </div>
            </div>
            </form>
        `;
    }

    function attachMetadataFormListeners() {
        const form = document.getElementById('metadataForm');
        const saveBtn = document.getElementById('metadataSaveBtn');
        if (!form || !saveBtn || !currentMetadata) return;
        const checkDirty = () => {
            const categoryId = form.querySelector('[name="category_id"]').value;
            const boxId = form.querySelector('[name="box_id"]').value;
            const tagsValue = form.querySelector('[name="tags"]').value.trim();
            
            const origCat = currentMetadata.category_id == null ? '' : String(currentMetadata.category_id);
            const origBox = currentMetadata.box_id == null ? '' : String(currentMetadata.box_id);
            const origTags = (currentMetadata.tags || []).join(', ');

            const dirty = (
                categoryId !== origCat ||
                boxId !== origBox ||
                tagsValue !== origTags
            );
            saveBtn.disabled = !dirty;
        };
        setupMetadataHierarchyFilters(form);
        setupLocationCascade(form);
        form.querySelectorAll('input, select, textarea').forEach(el => {
            el.addEventListener('input', checkDirty);
            el.addEventListener('change', checkDirty);
        });
        checkDirty();
        
        // Auto-update hierarchy when category changes
        const categorySelect = form.querySelector('[name="category_id"]');
        if (categorySelect && currentMetadata.categories) {
            categorySelect.addEventListener('change', function() {
                const selectedCategoryId = this.value;
                const selectedCategory = currentMetadata.categories.find(c => String(c.id) === String(selectedCategoryId));
                
                if (selectedCategory && selectedCategory.service_id) {
                    const relatedService = currentMetadata.services.find(s => String(s.id) === String(selectedCategory.service_id));
                    if (relatedService) {
                        // Update service (locked field)
                        const serviceSelect = form.querySelector('[name="service_id"]');
                        if (serviceSelect) {
                            serviceSelect.value = relatedService.id;
                        }
                        
                        // Update sub-department (locked field)
                        if (relatedService.sub_department_id) {
                            const relatedSubDept = currentMetadata.sub_departments.find(sd => String(sd.id) === String(relatedService.sub_department_id));
                            if (relatedSubDept) {
                                const subDeptSelect = form.querySelector('[name="sub_department_id"]');
                                if (subDeptSelect) {
                                    subDeptSelect.value = relatedSubDept.id;
                                }
                                
                                // Update department (locked field)
                                if (relatedSubDept.department_id) {
                                    const relatedDept = currentMetadata.departments.find(d => String(d.id) === String(relatedSubDept.department_id));
                                    if (relatedDept) {
                                        const deptSelect = form.querySelector('[name="department_id"]');
                                        if (deptSelect) {
                                            deptSelect.value = relatedDept.id;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                checkDirty();
            });
        }
    }

    function setupLocationCascade(form) {
        const roomSelect = form.querySelector('[name="room_id"]');
        const rowSelect = form.querySelector('[name="row_id"]');
        const shelfSelect = form.querySelector('[name="shelf_id"]');
        const boxSelect = form.querySelector('[name="box_id"]');
        
        if (!roomSelect || !rowSelect || !shelfSelect || !boxSelect) return;

        function filterRows() {
            const roomId = roomSelect.value;
            rowSelect.disabled = !roomId;
            shelfSelect.disabled = true;
            boxSelect.disabled = true;
            
            Array.from(rowSelect.options).forEach(option => {
                if (!option.value) return;
                const matches = !roomId || option.dataset.roomId === roomId;
                option.hidden = !matches;
                if (!matches && option.selected) {
                    option.selected = false;
                    rowSelect.value = '';
                }
            });
            filterShelves();
        }

        function filterShelves() {
            const rowId = rowSelect.value;
            shelfSelect.disabled = !rowId;
            boxSelect.disabled = true;
            
            Array.from(shelfSelect.options).forEach(option => {
                if (!option.value) return;
                const matches = !rowId || option.dataset.rowId === rowId;
                option.hidden = !matches;
                if (!matches && option.selected) {
                    option.selected = false;
                    shelfSelect.value = '';
                }
            });
            filterBoxes();
        }

        function filterBoxes() {
            const shelfId = shelfSelect.value;
            boxSelect.disabled = !shelfId;
            
            Array.from(boxSelect.options).forEach(option => {
                if (!option.value) return;
                const matches = !shelfId || option.dataset.shelfId === shelfId;
                option.hidden = !matches;
                if (!matches && option.selected) {
                    option.selected = false;
                    boxSelect.value = '';
                }
            });
        }

        roomSelect.addEventListener('change', filterRows);
        rowSelect.addEventListener('change', filterShelves);
        shelfSelect.addEventListener('change', filterBoxes);

        // Initial filtering based on current values
        filterRows();
    }

    function submitMetadataChanges(documentId) {
        const form = document.getElementById('metadataForm');
        const saveBtn = document.getElementById('metadataSaveBtn');
        if (!form || !saveBtn || !currentMetadata) return;

        const payload = {};
        const categoryId = form.querySelector('[name="category_id"]').value;
        const boxId = form.querySelector('[name="box_id"]').value;
        const tagsValue = form.querySelector('[name="tags"]').value.trim();

        if (categoryId !== (currentMetadata.category_id == null ? '' : String(currentMetadata.category_id))) {
            payload.category_id = categoryId || null;
        }

        if (boxId !== (currentMetadata.box_id == null ? '' : String(currentMetadata.box_id))) {
            payload.box_id = boxId || null;
        }

        // Parse tags from comma-separated string to array
        const newTags = tagsValue ? tagsValue.split(',').map(t => t.trim()).filter(t => t.length > 0) : [];
        const origTags = currentMetadata.tags || [];
        if (JSON.stringify(newTags.sort()) !== JSON.stringify(origTags.sort())) {
            payload.tags = newTags;
        }

        if (Object.keys(payload).length === 0) {
            return;
        }

        saveBtn.disabled = true;

        fetch(`/documents/${documentId}/metadata`, {
            method: 'PUT',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
            },
            body: JSON.stringify(payload),
            credentials: 'same-origin'
        })
        .then(resp => resp.json())
        .then(data => {
            if (data.success && data.metadata) {
                currentMetadata = data.metadata;
                document.getElementById('metadataContent').innerHTML = formatMetadataEditable(currentMetadata);
                attachMetadataFormListeners();
                
                // Show success notification
                showToast('{{ ui_t("pages.upload.metadata_updated") }}', 'success');
                
                // Reset save button after brief delay
                setTimeout(() => {
                    saveBtn.disabled = true;
                }, 100);
            } else {
                alert(data.message || 'Failed to save metadata');
                saveBtn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error saving metadata:', error);
            alert('An error occurred while saving metadata');
            saveBtn.disabled = false;
        });
    }
    
    function setupMetadataHierarchyFilters(form) {
        // Filters are disabled since organizational structure fields are read-only
        // Only category field is editable
    }

    function getStatusColor(status) {
        const colors = {
            'approved': 'success',
            'pending': 'warning',
            'declined': 'danger',
            'archived': 'secondary'
        };
        return colors[status] || 'secondary';
    }
</script>
