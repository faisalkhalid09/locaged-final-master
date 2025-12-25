<div class="mt-5">
    @if($pageTitle)
        <div class="mb-4">
            <h2 class="fw-bold">{{ ui_t('pages.headings.' . $pageTitle) }}</h2>
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

            <button
                type="button"
                class="btn-export"
                wire:click="downloadSelected"
            >
                <i class="fa-solid fa-arrow-up-from-bracket" style="color: #e63946"></i>
                {{ ui_t('pages.documents.export') }}
            </button>
            @role('master')
                @if(count($checkedDocuments) > 0)
                    <button
                        type="button"
                        class="btn btn-danger text-white d-inline-flex gap-1 align-items-center ms-2"
                        style="font-size: 0.9rem;"
                        wire:click="bulkDelete"
                        onclick="return confirm('{{ ui_t('pages.documents.bulk_permanent_delete_confirm', ['count' => count($checkedDocuments)]) }}')"
                    >
                        <i class="fas fa-trash-can"></i> {{ ui_t('pages.actions.bulk_delete') }} ({{ count($checkedDocuments) }})
                    </button>
                @endif
            @endrole
            @can('create', \App\Models\Document::class)
                <a href="{{ route('documents.create', ['category_id' => $filterId]) }}" class="btn btn-dark text-white d-inline-flex gap-1 align-items-center" style="font-size: 0.9rem;">
                    <i class="fas fa-plus"></i> {{ ui_t('pages.documents.upload_documents') }}
                </a>
            @endcan



        </div>
    </div>

    <div class="recent-files-section">
        @if($contextLabel)
            <div class="mb-2 text-muted fw-semibold">
                {{ __('Documents under') }} {{ $contextLabel }}
            </div>
        @endif

        @if($room)
            <div class="alert alert-info d-flex align-items-center justify-content-between mb-3" role="alert">
                <div class="d-flex align-items-center">
                    <i class="fas fa-map-marker-alt me-2"></i>
                    <span><strong>{{ ui_t('filters.favorites_only') }}</strong> {{ ui_t('pages.total') }} {{ $room }}</span>
                </div>
                <button type="button" class="btn btn-sm btn-outline-info" wire:click="$set('room', '')" title="{{ ui_t('filters.reset_filters') }}">
                    <i class="fas fa-times"></i> {{ ui_t('filters.reset_filters') }}
                </button>
            </div>
        @endif
        <div class="table-controls mb-5">
            <div class="search-files">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="{{ ui_t('tables.file_name') }}" wire:model.live="search" />
            </div>
            <div class="table-filters">
                <select class="form-select" wire:model.change="status">
                    <option value="all">{{ ui_t('filters.all') }}</option>
                    @foreach(\App\Enums\DocumentStatus::activeCases() as $status)
                        <option value="{{ $status->value }}">{{ ui_t('pages.documents.status.' . $status->value) }}</option>
                    @endforeach

                </select>

                <select class="form-select" wire:model.change="fileType">
                    <option value="">{{ ui_t('filters.file_type') }}</option>
                    <option value="pdf">{{ ui_t('filters.types.pdf') }}</option>
                    <option value="doc">{{ ui_t('filters.types.word') ?? ui_t('filters.types.doc') }}</option>
                    <option value="image">{{ ui_t('filters.types.image') }}</option>
                    <option value="excel">{{ ui_t('filters.types.excel') }}</option>
                    <option value="video">{{ ui_t('filters.types.video') }}</option>
                    <option value="audio">{{ ui_t('filters.types.audio') }}</option>
                </select>

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
                </style>
                <div class="fav-toggle-wrap ms-2 d-flex align-items-center">
                    <input type="checkbox" id="favoritesOnlyCat" class="fav-toggle-input" wire:model.change="favoritesOnly">
                    <label for="favoritesOnlyCat"
                           class="fav-toggle {{ $favoritesOnly ? 'is-active' : '' }}"
                           aria-pressed="{{ $favoritesOnly ? 'true' : 'false' }}"
                           role="button"
                           title="{{ ui_t('filters.favorites_only') }}">
                        <i class="{{ $favoritesOnly ? 'fa-solid' : 'fa-regular' }} fa-star"></i>
                        <span class="d-none d-md-inline">{{ ui_t('filters.favorites_only') }}</span>
                    </label>
                </div>

                <!-- Per Page Selector -->
                <select class="form-select" wire:model.change="perPage" style="max-width: 120px;">
                    <option value="10">10 per page</option>
                    <option value="50">50 per page</option>
                    <option value="100">100 per page</option>
                    <option value="250">250 per page</option>
                    <option value="500">500 per page</option>
                </select>

                <!-- Reset button -->
                <button type="button" wire:click="resetFilters" class="btn btn-sm btn-outline-danger text-nowrap">
                    {{ ui_t('filters.reset_filters') }}
                </button>

            </div>
        </div>

        <table class="files-table">
            <thead>
            <tr>
                <th>
                    <input type="checkbox" wire:model.change="selectAll">
                </th>
                <th>{{ ui_t('tables.file_name') }}</th>
                {{-- <th>{{ ui_t('tables.last_version') }}</th> --}}
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
                    {{-- <td>
                        <div>{{ $doc->latestVersion?->version_number }}</div>
                    </td> --}}
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
                            @if($doc->expire_at && $doc->expire_at->isPast())
                                <span class="badge bg-danger" style="font-size: 0.7rem;">{{ ui_t('pages.destructions.status_values.expired') }}</span>
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
                                    <a href="{{ route('document-versions.fullscreen',['id' => $doc->latestVersion->id]) }}"
                                       class="btn-table btn-table-preview" title="{{ ui_t('pages.documents.preview') }}" aria-label="{{ ui_t('pages.documents.preview') }}">
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
                                           data-extra-fields='@json([["type" => "text", "name" => "title", "placeholder" => ui_t("pages.documents.rename_placeholder")]])'
                                        >
                                            <i class="fa-solid fa-pen-to-square"></i> {{ ui_t('pages.documents.rename_button') }}
                                        </a>
                                    </li>
                                    @endcan

                                    @if(auth()->user()?->hasRole('master'))
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
                                                                        <li><a class="dropdown-item" href="#"><i class="fa-regular fa-star"></i> {{ ui_t('pages.documents.favorite.add') }}</a></li>
                                    --}}
                                </ul>

                            </div>
                        </div>
                    </td>
                </tr>
                @can('viewAny', \App\Models\User::class)
                <tr class="log-row d-none" data-doc-id="{{ $doc->id }}">
                    <td colspan="10">
                        @foreach($doc->auditLogs as $log)
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
                                                />
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
                        @endforeach

                    </td>
                </tr>
                @endcan
                @include('components.modals.move-documents-modal',['document' => $doc,'rooms' => $rooms])
                @include('components.modals.document-destruction-modal',['document' => $doc,'movements' => $movements])
            @endforeach


            </tbody>
        </table>


        @include('components.modals.confirm-modal')
        <x-pagination :items="$documents"></x-pagination>
    </div>

</div>


<script>
    document.addEventListener('DOMContentLoaded', function () {
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

    // ==================== Show Document Metadata Function (category view) ====================
    const CATEGORY_METADATA_CSRF = '{{ csrf_token() }}';
    let categoryCurrentMetadata = null;

    function showDocumentMetadata(documentId) {
        let modalEl = document.getElementById('documentMetadataModal');
        if (!modalEl) {
            const wrapper = document.createElement('div');
            wrapper.innerHTML = `
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
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ ui_t('actions.close') }}</button>
                                <button type="button" class="btn btn-primary" id="metadataSaveBtn" disabled>{{ ui_t('pages.upload.save') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(wrapper.firstElementChild);
            modalEl = document.getElementById('documentMetadataModal');
        }

        const metadataModal = new bootstrap.Modal(modalEl);
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
                categoryCurrentMetadata = data.metadata;
                metadataContent.innerHTML = formatMetadataEditableCategory(categoryCurrentMetadata);
                attachCategoryMetadataListeners();
                if (saveBtn) {
                    saveBtn.onclick = () => submitCategoryMetadataChanges(categoryCurrentMetadata.id);
                }
            } else {
                metadataContent.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        ${data.message || '{{ __('Failed to load metadata') }}'}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error fetching metadata:', error);
            metadataContent.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    {{ __('An error occurred while loading metadata') }}
                </div>
            `;
        });
    }

    function formatMetadataEditableCategory(metadata) {
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


        // Filter categories to only show those belonging to the document's service
        const filteredCategories = metadata.service_id
            ? categories.filter(c => String(c.service_id) === String(metadata.service_id))
            : categories;

        const categoryOptions = filteredCategories.map(c => `
            <option value="${c.id}" data-service-id="${c.service_id}" ${String(c.id) == String(metadata.category_id ?? '') ? 'selected' : ''}>${c.name}</option>
        `).join('');


        return `
            <form id="metadataForm">
            <div class="metadata-container">
                <div class="metadata-section mb-4">
                    <h6 class="text-primary border-bottom pb-2 mb-3">
                        <i class="fas fa-file me-2"></i>{{ ui_t('pages.upload.document_information') }}
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="text-muted small d-block">{{ ui_t('pages.upload.document_title') }}</label>
                            <input type="text" class="form-control" value="${metadata.title || ''}" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small d-block">{{ ui_t('tables.status') }}</label>
                            <span class="badge bg-${getStatusColor(metadata.status)}">${metadata.status || 'N/A'}</span>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small d-block">{{ ui_t('tables.created_at') }}</label>
                            <input type="text" class="form-control" value="${metadata.created_at || 'N/A'}" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small d-block">{{ ui_t('pages.upload.expires_at') }}</label>
                            <input type="date" class="form-control" value="${expireValue}" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small d-block">{{ ui_t('pages.upload.created_by') }}</label>
                            <input type="text" class="form-control" value="${metadata.created_by || 'N/A'}" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small d-block">{{ ui_t('pages.upload.file_type') }}</label>
                            <input type="text" class="form-control" value="${metadata.file_type ? metadata.file_type.toUpperCase() : 'N/A'}" disabled>
                        </div>
                    </div>
                </div>

                <div class="metadata-section mb-4">
                    <h6 class="text-primary border-bottom pb-2 mb-3">
                        <i class="fas fa-sitemap me-2"></i>{{ ui_t('pages.upload.organizational_structure') }}
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="text-muted small d-block">{{ ui_t('pages.upload.pole') }}</label>
                            <select class="form-select" disabled>
                                ${deptOptions}
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted small d-block">{{ ui_t('pages.upload.department') }}</label>
                            <select class="form-select" disabled>
                                ${subDeptOptions}
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted small d-block">{{ __('Service') }}</label>
                            <select class="form-select" disabled>
                                ${serviceOptions}
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="text-muted small d-block">{{ ui_t('pages.upload.category') }}</label>
                            <select name="category_id" class="form-select">
                                ${categoryOptions}
                            </select>
                        </div>
                    </div>
                </div>

                <div class="metadata-section mb-4">
                    <h6 class="text-primary border-bottom pb-2 mb-3">
                        <i class="fas fa-map-marker-alt me-2"></i>{{ ui_t('pages.upload.physical_location') }}
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="text-muted small d-block">{{ ui_t('pages.upload.room') }}</label>
                            <select name="room_id" class="form-select" data-location-level="room">
                                <option value="">{{ ui_t('pages.upload.select_room_first') }}</option>
                                ${(metadata.rooms || []).map(room => `
                                    <option value="${room.id}" ${String(room.id) == String(metadata.room_id ?? '') ? 'selected' : ''}>${room.name}</option>
                                `).join('')}
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small d-block">{{ ui_t('pages.upload.row') }}</label>
                            <select name="row_id" class="form-select" data-location-level="row" ${!metadata.room_id ? 'disabled' : ''}>
                                <option value="">{{ ui_t('pages.upload.select_row_first') }}</option>
                                ${(metadata.rows || []).map(row => `
                                    <option value="${row.id}" data-room-id="${row.room_id}" ${String(row.id) == String(metadata.row_id ?? '') ? 'selected' : ''}>${row.name}</option>
                                `).join('')}
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small d-block">{{ ui_t('pages.upload.shelf') }}</label>
                            <select name="shelf_id" class="form-select" data-location-level="shelf" ${!metadata.row_id ? 'disabled' : ''}>
                                <option value="">{{ ui_t('pages.upload.select_shelf_first') }}</option>
                                ${(metadata.shelves || []).map(shelf => `
                                    <option value="${shelf.id}" data-row-id="${shelf.row_id}" ${String(shelf.id) == String(metadata.shelf_id ?? '') ? 'selected' : ''}>${shelf.name}</option>
                                `).join('')}
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small d-block">{{ ui_t('pages.upload.box') }}</label>
                            <select name="box_id" class="form-select" data-location-level="box" ${!metadata.shelf_id ? 'disabled' : ''}>
                                <option value="">{{ ui_t('pages.upload.select_box_first') }}</option>
                                ${(metadata.boxes || []).map(box => `
                                    <option value="${box.id}" data-shelf-id="${box.shelf_id}" ${String(box.id) == String(metadata.box_id ?? '') ? 'selected' : ''}>${box.name}</option>
                                `).join('')}
                            </select>
                        </div>
                    </div>
                </div>

                <div class="metadata-section mb-4">
                    <h6 class="text-primary border-bottom pb-2 mb-3">
                        <i class="fas fa-tags me-2"></i>{{ ui_t('pages.upload.tags') }}
                    </h6>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="text-muted small d-block">{{ ui_t('pages.upload.tags_comma_separated') }}</label>
                            <input type="text" name="tags" class="form-control" 
                                   value="${(metadata.tags || []).join(', ')}" 
                                   placeholder="{{ ui_t('pages.upload.enter_tags_comma') }}">
                            <small class="text-muted">{{ ui_t('pages.upload.new_tags_hint') }}</small>
                        </div>
                    </div>
                </div>
            </div>
            </form>
        `;
    }

    function attachCategoryMetadataListeners() {
        const form = document.getElementById('metadataForm');
        const saveBtn = document.getElementById('metadataSaveBtn');
        if (!form || !saveBtn || !categoryCurrentMetadata) return;
        const checkDirty = () => {
            const categoryId = form.querySelector('[name="category_id"]').value;
            const boxId = form.querySelector('[name="box_id"]').value;
            const tagsValue = form.querySelector('[name="tags"]').value.trim();
            
            const origCat = categoryCurrentMetadata.category_id == null ? '' : String(categoryCurrentMetadata.category_id);
            const origBox = categoryCurrentMetadata.box_id == null ? '' : String(categoryCurrentMetadata.box_id);
            const origTags = (categoryCurrentMetadata.tags || []).join(', ');

            const dirty = (
                categoryId !== origCat ||
                boxId !== origBox ||
                tagsValue !== origTags
            );
            saveBtn.disabled = !dirty;
        };
        setupCategoryMetadataHierarchyFilters(form);
        setupCategoryLocationCascade(form);
        form.querySelectorAll('input, select, textarea').forEach(el => {
            el.addEventListener('input', checkDirty);
            el.addEventListener('change', checkDirty);
        });
        checkDirty();
    }

    function setupCategoryLocationCascade(form) {
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

    function submitCategoryMetadataChanges(documentId) {
        const form = document.getElementById('metadataForm');
        const saveBtn = document.getElementById('metadataSaveBtn');
        if (!form || !saveBtn || !categoryCurrentMetadata) return;

        const payload = {};
        const categoryId = form.querySelector('[name="category_id"]').value;
        const boxId = form.querySelector('[name="box_id"]').value;
        const tagsValue = form.querySelector('[name="tags"]').value.trim();

        if (categoryId !== (categoryCurrentMetadata.category_id == null ? '' : String(categoryCurrentMetadata.category_id))) {
            payload.category_id = categoryId || null;
        }

        if (boxId !== (categoryCurrentMetadata.box_id == null ? '' : String(categoryCurrentMetadata.box_id))) {
            payload.box_id = boxId || null;
        }

        // Parse tags from comma-separated string to array
        const newTags = tagsValue ? tagsValue.split(',').map(t => t.trim()).filter(t => t.length > 0) : [];
        const origTags = categoryCurrentMetadata.tags || [];
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
                'X-CSRF-TOKEN': CATEGORY_METADATA_CSRF,
            },
            body: JSON.stringify(payload),
            credentials: 'same-origin'
        })
        .then(resp => resp.json())
        .then(data => {
            if (data.success && data.metadata) {
                categoryCurrentMetadata = data.metadata;
                document.getElementById('metadataContent').innerHTML = formatMetadataEditableCategory(categoryCurrentMetadata);
                attachCategoryMetadataListeners();
            } else {
                alert(data.message || 'Failed to save metadata');
            }
        })
        .catch(error => {
            console.error('Error saving metadata:', error);
            alert('An error occurred while saving metadata');
        });
    }

    function setupCategoryMetadataHierarchyFilters(form) {
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
