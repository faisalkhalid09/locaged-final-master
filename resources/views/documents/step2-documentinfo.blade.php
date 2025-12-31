@if($step === 2)
    <div>
        <h3 class="section-label py-4 px-5 px-md-0">
            <img src="{{ asset('assets/Clip path group (1).svg') }}" alt="{{ ui_t('actions.file') }}"> {{ ui_t('pages.upload.document_information') }}
        </h3>
        <div class="d-none" x-data x-init="$wire.currentInfo?.color || $wire.set('currentInfo.color','Blue')">
            <label class="form-label">{{ ui_t('pages.upload.organize_by_color') }} <span class="text-danger">*</span></label>
            <div class="d-flex gap-3 align-items-center">
                <label class="d-inline-flex align-items-center">
                    <input type="radio" class="form-check-input me-2" name="color" value="Blue" wire:model="currentInfo.color">
                    <span>{{ ui_t('pages.upload.color_blue') }}</span>
                </label>
                <label class="d-inline-flex align-items-center">
                    <input type="radio" class="form-check-input me-2" name="color" value="Red" wire:model="currentInfo.color">
                    <span>{{ ui_t('pages.upload.color_red') }}</span>
                </label>
                <label class="d-inline-flex align-items-center">
                    <input type="radio" class="form-check-input me-2" name="color" value="Green" wire:model="currentInfo.color">
                    <span>{{ ui_t('pages.upload.color_green') }}</span>
                </label>
            </div>
        </div>
        <div class="row g-4 align-items-stretch">
            <div class="col-lg-6 d-flex">
                <div class="form-box shadow-sm bg-white p-0 w-100 h-100 d-flex align-items-center justify-content-center">
                    @if($currentPreviewUrl && $this->currentPreviewType === 'image')
                        <img src="{{ $currentPreviewUrl }}" class="img-fluid w-100 h-100" style="object-fit: contain;" alt="{{ ui_t('pages.upload.image_preview') }}" />
                    @elseif($currentPreviewUrl && $this->currentPreviewType === 'pdf')
                        {{-- Only PDFs are embedded in an iframe. Other file types (Word, Excel, etc.)
                             will not trigger a browser auto-download and just show the placeholder. --}}
                        <iframe src="{{ $currentPreviewUrl }}" class="w-100 h-100 border-0"></iframe>
                    @else
                        <div class="text-muted p-3">{{ ui_t('pages.upload.no_preview') }}</div>
                    @endif
                </div>
            </div>

            <div class="col-lg-6 d-flex">
                <div class="form-box shadow-sm bg-white p-4 w-100 h-100">
                    <div class="row">
                        <!-- File Name -->
                        @php
                            $multiUpload = count($documentInfos) > 1;
                        @endphp
                        @if(count($documentInfos) === 1 || ($multiUpload && !$useSharedMetadata))
                            <div class="col-md-12 mb-3">
                                <label class="form-label">{{ ui_t('pages.upload.file_name') }}<span class="text-danger">*</span></label>
                                <input type="text" class="form-control" placeholder="{{ ui_t('pages.upload.file_name') }}" required
                                       wire:model="currentInfo.title"
                                />
                            </div>
                        @endif

                        <!-- Organizational hierarchy: Department = Sub-Department = Service -->
                        <div class="col-md-12 mb-3">
                            @php
                                $selectedDepartmentId    = $currentInfo['department_id'] ?? null;
                                $selectedSubDepartmentId = $currentInfo['sub_department_id'] ?? null;
                                $selectedServiceId       = $currentInfo['service_id'] ?? null;
                                $selectedCategoryId      = $currentInfo['category_id'] ?? null;
                                // When coming from a category page, the Livewire component receives
                                // a non-null $categoryId and preselects the whole hierarchy. In that
                                // case we also lock these fields so they cannot be changed.
                                $lockHierarchyFromCategory = !is_null($categoryId ?? null);
                            @endphp

                            <label class="form-label">{{ ui_t('pages.upload.pole') }} / {{ ui_t('pages.upload.department') }} / {{ ui_t('pages.upload.services') }} <span class="text-danger">*</span></label>

                            <div class="row g-3">
                                {{-- Department (from department_user) --}}
                                <div class="col-md-4">
                                    <label class="form-label small mb-1">{{ ui_t('pages.upload.pole') }}</label>
                                    <select class="form-select @error('currentInfo.department_id') is-invalid @enderror"
                                            required
                                            wire:model.change="currentInfo.department_id"
                                            @if($lockHierarchyFromCategory) disabled @endif>
                                        <option value="">{{ ui_t('pages.upload.select_department') }}</option>
                                        @foreach($userDepartments as $dept)
                                            <option value="{{ $dept->id }}" @selected($selectedDepartmentId == $dept->id)>{{ $dept->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('currentInfo.department_id')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Sub-Department (from sub_department_user, filtered by selected department) --}}
                                <div class="col-md-4">
                                    <label class="form-label small mb-1">{{ ui_t('pages.upload.department') }}</label>
                                    <select class="form-select"
                                            wire:model.change="currentInfo.sub_department_id"
                                            @if(!$selectedDepartmentId || $lockHierarchyFromCategory) disabled @endif>
                                        <option value="">{{ ui_t('pages.upload.select_sub_department') }}</option>
                                        @foreach($userSubDepartments as $sub)
                                            @if($selectedDepartmentId && $sub->department_id == $selectedDepartmentId)
                                                <option value="{{ $sub->id }}" @selected($selectedSubDepartmentId == $sub->id)>
                                                    {{ $sub->name }}
                                                </option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Service (from service_user, filtered by selected sub-department) --}}
                                <div class="col-md-4">
                                    <label class="form-label small mb-1">{{ ui_t('pages.upload.services') }}</label>
                                    <select class="form-select @error('currentInfo.service_id') is-invalid @enderror"
                                            wire:model.change="currentInfo.service_id"
                                            @if(!$selectedSubDepartmentId || $lockHierarchyFromCategory) disabled @endif>
                                        <option value="">{{ ui_t('pages.upload.select_service') }}</option>
                                        @foreach($userServices as $service)
                                            @if($selectedSubDepartmentId && $service->sub_department_id == $selectedSubDepartmentId)
                                                <option value="{{ $service->id }}" @selected($selectedServiceId == $service->id)>
                                                    {{ $service->name }}
                                                </option>
                                            @endif
                                        @endforeach
                                    </select>
                                    @error('currentInfo.service_id')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <small class="text-muted d-block mt-1">
                                @if($lockHierarchyFromCategory)
                                    {{ ui_t('pages.upload.hierarchy_locked_message') }}
                                @else
                                    {{ ui_t('pages.upload.hierarchy_help_text') }}
                                @endif
                            </small>
                        </div>

                        <!-- Category (filtered by selected Service) -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ ui_t('pages.upload.category') }}<span class="text-danger">*</span></label>
                            <select id="categorySelect"
                                    class="form-select @error('currentInfo.category_id') is-invalid @enderror"
                                    required
                                    wire:model.change="currentInfo.category_id"
                                    @if(!$selectedServiceId || $lockHierarchyFromCategory) disabled @endif>
                                <option value="">{{ ui_t('pages.upload.select_category') }}</option>
                                @foreach($categories as $category)
                                    @if(!$selectedServiceId || $category->service_id == $selectedServiceId)
                                        <option value="{{ $category->id }}" @selected($selectedCategoryId == $category->id)>
                                            {{ $category->name }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                            @error('currentInfo.category_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            @if(!$selectedServiceId && !$lockHierarchyFromCategory)
                                <small class="text-muted">{{ ui_t('pages.upload.select_service_first') }}</small>
                            @endif
                        </div>

                        <!-- Subcategory (filtered by selected Category, now optional) -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ ui_t('pages.upload.subcategory') }}</label>
                            <select id="subcategorySelect"
                                    class="form-select @error('currentInfo.subcategory_id') is-invalid @enderror"
                                    wire:model.change="currentInfo.subcategory_id"
                                    @if(!$selectedCategoryId) disabled @endif>
                                <option value="" selected>{{ ui_t('pages.upload.select_subcategory') }}</option>
                                @foreach($subcategories as $subcategory)
                                    @if(!$selectedCategoryId || $subcategory->category_id == $selectedCategoryId)
                                        <option value="{{ $subcategory->id }}">
                                            {{ $subcategory->name }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                            @error('currentInfo.subcategory_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            @if(!$selectedCategoryId)
                                <small class="text-muted">{{ ui_t('pages.upload.select_category_first') }}</small>
                            @endif
                        </div>

                        <!-- Creation Date -->
                        <div class="col-md-6">
                            <label for="created_at" class="form-label">{{ ui_t('pages.upload.date_and_time') }} <span class="text-danger">*</span></label>
                            <input
                                type="datetime-local"
                                class="form-control @error('currentInfo.created_at') is-invalid @enderror"
                                id="created_at"
                                wire:model.change="currentInfo.created_at"
                                required
                            >
                            @error('currentInfo.created_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Expire Date (always read-only; computed from selected category policy) -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ ui_t('pages.upload.expire_date') }}<span class="text-danger">*</span></label>
                            @php
                                $selCat = ($currentInfo['category_id'] ?? null) ? $categories->firstWhere('id', $currentInfo['category_id']) : null;
                                $autoExpiry = $selCat && $selCat->expiry_value && $selCat->expiry_unit;
                            @endphp
                            <input type="date" class="form-control @error('currentInfo.expire_at') is-invalid @enderror" required
                                   wire:model="currentInfo.expire_at"
                                   readonly style="background-color: #f8f9fa; cursor: not-allowed;"
                            />
                            @error('currentInfo.expire_at')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            @if($autoExpiry)
                                <small class="text-muted">{{ ui_t('pages.upload.expiry_hint', ['value' => $selCat->expiry_value, 'unit' => $selCat->expiry_unit]) }}</small>
                            @else
                                <small class="text-muted">{{ ui_t('pages.upload.select_subcategory_hint') }}</small>
                            @endif
                        </div>

                        <!-- Add New Tags (free text) -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ ui_t('pages.upload.add_new_tags') }}</label>
                            <input type="text" class="form-control" placeholder="{{ ui_t('pages.upload.new_tags_placeholder') }}"
                                   wire:model.lazy="currentInfo.new_tags"
                            />
                            <small class="text-muted">{{ ui_t('pages.upload.new_tags_hint') }}</small>
                        </div>

                        <!-- Tags (multiple select, optional) -->
                        <div class="col-md-6 mb-3">
                            <label for="tags" class="form-label">{{ ui_t('pages.upload.tags') }}</label>
                            <select multiple class="form-select @error('currentInfo.tags') is-invalid @enderror"
                                    wire:model="currentInfo.tags">
                                @foreach($tags as $tag)
                                    <option value="{{ $tag->id }}">{{ $tag->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Physical Location (Hierarchical) -->
                        <div class="col-md-12 mb-3">
                            <label class="form-label">
                                {{ ui_t('pages.upload.physical_location') }} <span class="text-danger">*</span>
                            </label>
                            
                            <!-- Step 1: Room -->
                            <div class="row g-2 mb-2">
                                <div class="col-md-3">
                                    <label class="form-label small">1. {{ ui_t('pages.upload.room') }}</label>
                                    <select class="form-select form-select-sm" wire:model.live="selectedRoomId">
                                        <option value="">{{ ui_t('pages.upload.select_room') }}</option>
                                        @foreach($rooms as $room)
                                            <option value="{{ $room->id }}">{{ $room->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                <!-- Step 2: Row -->
                                <div class="col-md-3">
                                    <label class="form-label small">2. {{ ui_t('pages.upload.row') }}</label>
                                    <select class="form-select form-select-sm" wire:model.live="selectedRowId" 
                                            @if(!$selectedRoomId) disabled @endif>
                                        <option value="">{{ ui_t('pages.upload.select_row') }}</option>
                                        @foreach($this->rows as $row)
                                            <option value="{{ $row->id }}">{{ $row->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                <!-- Step 3: Shelf -->
                                <div class="col-md-3">
                                    <label class="form-label small">3. {{ ui_t('pages.upload.shelf') }}</label>
                                    <select class="form-select form-select-sm" wire:model.live="selectedShelfId"
                                            @if(!$selectedRowId) disabled @endif>
                                        <option value="">{{ ui_t('pages.upload.select_shelf') }}</option>
                                        @foreach($this->shelves as $shelf)
                                            <option value="{{ $shelf->id }}">{{ $shelf->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                <!-- Step 4: Box -->
                                <div class="col-md-3">
                                    <label class="form-label small">4. {{ ui_t('pages.upload.box') }}</label>
                            <select class="form-select form-select-sm @error('currentInfo.box_id') is-invalid @enderror" wire:model.live="selectedBoxId"
                                            @if(!$selectedShelfId) disabled @endif required>
                                        <option value="">{{ ui_t('pages.upload.select_box') }}</option>
                                        @foreach($this->boxes as $box)
                                            <option value="{{ $box->id }}">{{ $box->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            
                            @error('currentInfo.box_id')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">{{ ui_t('pages.upload.location_structure_hint') }}</small>
                        </div>

                        <!-- Author (Read-Only) -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ ui_t('pages.upload.author') }}</label>
                            <input type="text" class="form-control" 
                                   value="{{ $currentInfo['author'] ?? '' }}" 
                                   readonly 
                                   style="background-color: #f8f9fa; cursor: not-allowed;"
                            />
                        </div>


                        <div class="d-flex justify-content-between mt-2">
                            @php
                                $user = auth()->user();
                                // Use the departments collection coming from Livewire (pivot-based and
                                // already bypassing global scopes) instead of reloading here.
                                $userDepartmentsForCheck = $userDepartments ?? collect();
                                $isPrivileged = $user && ($user->hasRole('master') || $user->hasRole('Super Administrator'));
                                $canProceed = $isPrivileged || $userDepartmentsForCheck->count() > 0;
                                $multiUpload = count($documentInfos) > 1;
                            @endphp

                            @if($multiUpload && $useSharedMetadata)
                                {{-- Multiple files with shared metadata: one form applies to all --}}
                                <button type="button" class="btn btn-outline-secondary" wire:click="prevStep">&lt; {{ ui_t('pages.upload.back') }}</button>
                                <button type="button" 
                                        class="btn btn-danger" 
                                        wire:click="submit"
                                        @if(!$canProceed) disabled title="{{ ui_t('pages.upload.must_assigned_to_submit') }}" @endif>
                                    {{ ui_t('pages.upload.submit') }}
                                </button>
                            @else
                                {{-- Per-file metadata (single file or multi with separate metadata) --}}
                                @if($currentDocumentIndex === 0)
                                    <button type="button" class="btn btn-outline-secondary" wire:click="prevStep">&lt; {{ ui_t('pages.upload.back') }}</button>
                                @else
                                    <button type="button" class="btn btn-outline-secondary" wire:click="prevDocument">&lt; {{ ui_t('pages.upload.back') }}</button>
                                @endif

                                @if($currentDocumentIndex < count($documentInfos) - 1)
                                    <button type="button" 
                                            class="btn btn-outline-secondary" 
                                            wire:click="nextDocument"
                                            @if(!$canProceed) disabled title="{{ ui_t('pages.upload.must_assigned_to_proceed') }}" @endif>
                                        {{ ui_t('pages.upload.next') }} &gt;
                                    </button>
                                @else
                                    <button type="button" 
                                            class="btn btn-danger" 
                                            wire:click="submit"
                                            @if(!$canProceed) disabled title="{{ ui_t('pages.upload.must_assigned_to_submit') }}" @endif>
                                        {{ ui_t('pages.upload.submit') }}
                                    </button>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Duplicate Warning Modal for Multi-Upload -->
        @if($showDuplicateModal)
            <div class="modal show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);" wire:ignore.self>
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-warning bg-opacity-10">
                            <h5 class="modal-title">
                                <i class="fa-solid fa-triangle-exclamation text-warning me-2"></i>
                                {{ ui_t('pages.upload.duplicate_warning_title') }}
                            </h5>
                        </div>

                        <div class="modal-body">
                            <p class="mb-3">{{ ui_t('pages.upload.duplicate_warning_intro') }}</p>
                            
                            <div class="alert alert-light border">
                                <h6 class="mb-0">{{ ui_t('pages.upload.duplicate_current_file', ['title' => $currentInfo['title'] ?? '—']) }}</h6>
                            </div>

                            <p class="mb-2 fw-semibold">{{ ui_t('pages.upload.existing_documents') }}</p>
                            <ul class="list-group mb-3">
                                @foreach($currentDuplicates as $dup)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>{{ $dup['title'] }}</span>
                                        <a href="{{ $dup['url'] }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="fa-solid fa-eye me-1"></i> {{ ui_t('pages.upload.view_document') }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>

                            <p class="text-muted small mb-0">
                                <i class="fa-solid fa-info-circle me-1"></i>
                                {{ ui_t('pages.upload.duplicate_question') }}
                            </p>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" wire:click="modifyCurrentFile">
                                <i class="fa-solid fa-pen-to-square me-1"></i> {{ ui_t('pages.upload.modify_current_file') }}
                            </button>
                            <button type="button" class="btn btn-outline-danger" wire:click="skipFile">
                                <i class="fa-solid fa-times me-1"></i> {{ ui_t('pages.upload.skip_file') }}
                            </button>
                            <button type="button" class="btn btn-success" wire:click="uploadAnyway">
                                <i class="fa-solid fa-check me-1"></i> {{ ui_t('pages.upload.upload_anyway') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

    </div>

    @push('styles')
    <style>
        @keyframes highlight-reset {
            0% { background-color: #fff3cd; }
            50% { background-color: #ffe69c; }
            100% { background-color: #ffffff; }
        }
        .subcategory-highlight {
            animation: highlight-reset 1s ease-in-out;
        }
    </style>
    @endpush

    @push('scripts')
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('categories-reset', () => {
                // Small delay to ensure DOM is updated
                setTimeout(() => {
                    const subcategorySelect = document.getElementById('subcategorySelect');
                    if (subcategorySelect) {
                        subcategorySelect.classList.add('subcategory-highlight');
                        setTimeout(() => {
                            subcategorySelect.classList.remove('subcategory-highlight');
                        }, 1000);
                    }
                }, 50);
            });
        });
    </script>
    @endpush
@endif
