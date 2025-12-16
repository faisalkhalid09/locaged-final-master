<div>
    <div class="header-left">
        <div class="search-box d-flex align-items-center">
            <div class="position-relative" style="width: fit-content;">
                <input
                    type="text"
                    name="search"
                    placeholder="{{ ui_t('actions.search') }}"
                    wire:model.live.debounce="query"
                    class="form-control pe-5"
                    autocomplete="off"
                    wire:keydown.enter.prevent="goToDocuments"
                />

                <!-- Persistent search icon over the input -->
                <i class="fas fa-magnifying-glass search-icon text-secondary"></i>

                <button
                    class="position-absolute border-0 bg-transparent text-secondary"
                    style="right: 8px; top: 50%; transform: translateY(-50%);"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#filterOverlay"
                >
                    <i class="fas fa-filter filter-icon" style="position: static;">
                        @if($this->activeFiltersCount > 0)
                            <span style="
            position: absolute;
            top: -6px;
            right: -8px;
            font-size: 10px;
            padding: 2px 4px;
            border-radius: 50%;
            background-color: red;
            color: white;
        ">
                {{ $this->activeFiltersCount }}

        </span>
                        @endif
                    </i>
                </button>

                <!-- Filter Overlay (absolute, does not push layout) -->
                <div id="filterOverlay" class="collapse position-absolute top-100 start-0 search-filter-overlay" style="z-index: 1050; min-width: 100%; max-width: 40rem;">
                    <div class="card shadow border-0 rounded-3 mt-2">
                        <div class="card-body p-3 p-md-4" style="max-height: 70vh; overflow: auto;">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0 fw-bold">{{ ui_t('filters.filters') }}</h6>
                                <button type="button" class="btn btn-sm btn-light" data-bs-toggle="collapse" data-bs-target="#filterOverlay" aria-label="{{ ui_t('actions.close') }}">{{ ui_t('actions.close') }}</button>
                            </div>

                            <form wire:submit.prevent="applyFilters">
                                <div class="row g-3">
                                    <!-- Document Type -->
                                    <div class="col-12 col-md-6">
                                        <label class="form-label">{{ ui_t('filters.file_type') }}</label>
                                        <select class="form-select" name="type" wire:model="filters.type">
                                            <option value="">{{ ui_t('filters.select_type') }}</option>
                                            <option value="pdf">{{ ui_t('filters.types.pdf') }}</option>
                                            <option value="doc">{{ ui_t('filters.types.word') }}</option>
                                            <option value="image">{{ ui_t('filters.types.image') }}</option>
                                            <option value="excel">{{ ui_t('filters.types.excel') }}</option>
                                            <option value="video">{{ ui_t('filters.types.video') }}</option>
                                            <option value="audio">{{ ui_t('filters.types.audio') }}</option>
                                        </select>
                                    </div>

                                    <!-- Creation Date Range -->
                                    <div class="col-12">
                                        <label class="form-label">{{ ui_t('pages.versions.creation_date') }}</label>
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <input
                                                    type="date"
                                                    id="creationStartDate"
                                                    name="creation_start"
                                                    class="form-control"
                                                    placeholder="{{ ui_t('filters.from') }}"
                                                    wire:model="filters.creation_start"
                                                >
                                            </div>
                                            <div class="col-6">
                                                <input
                                                    type="date"
                                                    id="creationEndDate"
                                                    name="creation_end"
                                                    class="form-control"
                                                    placeholder="{{ ui_t('filters.to') }}"
                                                    wire:model="filters.creation_end"
                                                >
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Keywords & Tags -->
                                    <div class="col-12 col-md-6">
                                        <label class="form-label">{{ ui_t('filters.keywords') }}</label>
                                        <input
                                            type="text"
                                            name="keywords"
                                            class="form-control"
                                            placeholder="{{ ui_t('filters.keywords_placeholder') }}"
                                            wire:model="filters.keywords"
                                        />
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label">{{ ui_t('filters.tags') }}</label>
                                        <input
                                            type="text"
                                            name="tags"
                                            class="form-control"
                                            placeholder="{{ ui_t('filters.tags_placeholder') }}"
                                            wire:model="filters.tags"
                                        />
                                    </div>

                                    <!-- Author -->
                                    <div class="col-12 col-md-6">
                                        <label class="form-label">{{ ui_t('filters.author') }}</label>
                                        <input
                                            type="text"
                                            name="author"
                                            class="form-control"
                                            placeholder="{{ ui_t('filters.author_placeholder') }}"
                                            wire:model="filters.author"
                                        >
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2 mt-3">
                                    <button type="button" wire:click="resetFilters" class="btn btn-outline-secondary">{{ ui_t('filters.reset_filters') }}</button>
                                    <button type="submit" class="btn btn-dark">{{ ui_t('pages.reports.apply') }}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Search Results Overlay (absolute, does not push layout) -->
                @if($results)
                    <div class="position-absolute top-100 start-0 search-results-overlay mt-2" style="z-index: 1040; min-width: 100%; max-width: 40rem;">
                        <div class="card shadow border-0 rounded-3">
                            <ul class="list-group list-group-flush">
                                @forelse($results as $version)
                                    <li class="list-group-item">
                                        <a href="{{ route('document-versions.preview',['id' => $version->id]) }}" class="text-decoration-none d-block">
                                            {{ $version->document?->title }}
                                        </a>
                                    </li>
                                @empty
                                    <li class="list-group-item text-muted">{{ ui_t('pages.messages.no_results') }}</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                @endif
            </div>
        </div>

    </div>

    <style>
        /* Scoped styles for the search filter overlay */
        .search-box .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 2;
            pointer-events: none;
        }
        /* Prevent global icon rule from affecting filter icon */
        .search-box .filter-icon {
            position: static !important;
            left: auto !important;
            top: auto !important;
            transform: none !important;
        }
        
        .search-filter-overlay .card {
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        }
        .search-filter-overlay .form-label {
            margin-bottom: 0.25rem;
            font-weight: 600;
        }
        .search-filter-overlay .form-control,
        .search-filter-overlay .form-select {
            width: 100%;
            min-height: 40px;
            font-size: 14px;
            padding-top: 10px;
            padding-bottom: 10px;
            padding-right: 12px;
            padding-left: 15px; /* override .search-box input { padding-left: 45px } */
        }
        .search-filter-overlay .btn {
            border-radius: 8px;
        }
        @media (max-width: 768px) {
            /* Ensure overlay fits on small screens */
            .search-filter-overlay {
                max-width: calc(100vw - 2rem) !important;
                right: 0;
                left: auto;
            }
        }
        
        /* Results overlay styling */
        .search-results-overlay .card {
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        }
        .search-results-overlay .list-group-item {
            padding: 0.75rem 1rem;
        }
        .search-results-overlay a:hover {
            text-decoration: underline;
        }
    </style>
</div>
