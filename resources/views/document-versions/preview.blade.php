@extends('layouts.app')

@section('content')
    @php 
        use Illuminate\Support\Str;
        // Use $document if passed directly (for expired documents), otherwise fall back to $doc->document
        $document = $document ?? ($doc->document ?? null);
        // Check if this is a destruction preview (should hide metadata sidebar)
        $isDestructionContext = request()->boolean('destruction');
    @endphp

    <div class="open-file mt-5 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="mb-0">{{ ui_t('pages.versions.preview_title') }}</h1>
            @if($isDestructionContext)
                <a href="{{ route('documents-destructions.index') }}" 
                   class="btn btn-outline-secondary" 
                   title="{{ __('Close') }}">
                    <i class="fas fa-times"></i> {{ __('Close') }}
                </a>
            @endif
        </div>
        <div class="row mb-5">
            {{-- Preview column: full width if destruction context, half otherwise --}}
            <div class="form-box {{ $isDestructionContext ? 'col-12' : 'col-md-6' }}" style="{{ $isDestructionContext ? 'min-height: 600px;' : '' }}">
                @if($fileType === 'image')
                    <img src="{{ $fileUrl }}" class="w-100" alt="{{ ui_t('pages.upload.image_preview') }}" />
                @elseif($fileType === 'pdf')
                    <iframe src="{{ $fileUrl }}" width="100%" height="100%" style="border:none;"></iframe>
                @elseif($fileType === 'doc')
                    @if(!empty($pdfUrl))
                        {{-- PDF preview for Word document converted by LibreOffice --}}
                        <iframe src="{{ $pdfUrl }}" width="100%" height="100%" style="border:none; min-height: 500px;"></iframe>
                    @else
                        <div class="d-flex align-items-center justify-content-center h-100">
                            <div class="text-center">
                                <i class="fas fa-file-word fa-5x text-primary mb-3"></i>
                                <h5>{{ ui_t('pages.versions.word_doc') }}</h5>
                                <p class="text-muted">{{ $document->title }}</p>
                                <a href="{{ $fileUrl }}" class="btn btn-primary" download>
                                    <i class="fas fa-download"></i> {{ ui_t('pages.versions.download') }}
                                </a>
                            </div>
                        </div>
                    @endif
                @elseif($fileType === 'excel')
                    @if(!empty($pdfUrl))
                        {{-- PDF preview for Excel document converted by LibreOffice --}}
                        <iframe src="{{ $pdfUrl }}" width="100%" height="100%" style="border:none; min-height: 500px;"></iframe>
                    @else
                        <div class="d-flex align-items-center justify-content-center h-100">
                            <div class="text-center">
                                <i class="fas fa-file-excel fa-5x text-success mb-3"></i>
                                <h5>{{ ui_t('pages.versions.excel_sheet') }}</h5>
                                <p class="text-muted">{{ $document->title }}</p>
                                <a href="{{ $fileUrl }}" class="btn btn-success" download>
                                    <i class="fas fa-download"></i> {{ ui_t('pages.versions.download') }}
                                </a>
                            </div>
                        </div>
                    @endif
                @elseif($fileType === 'video')
                    <video controls class="w-100" style="max-height: 100%;">
                        <source src="{{ $fileUrl }}" type="video/mp4">
                        <source src="{{ $fileUrl }}" type="video/avi">
                        <source src="{{ $fileUrl }}" type="video/mov">
                        {{ ui_t('pages.versions.video_not_supported') }}
                    </video>
                @elseif($fileType === 'audio')
                    <div class="d-flex align-items-center justify-content-center h-100">
                        <div class="text-center">
                            <i class="fas fa-file-audio fa-5x text-info mb-3"></i>
                            <h5>{{ ui_t('pages.versions.audio_file') }}</h5>
                            <p class="text-muted">{{ $document->title }}</p>
                            <audio controls class="w-100 mb-3">
                                <source src="{{ $fileUrl }}" type="audio/mpeg">
                                <source src="{{ $fileUrl }}" type="audio/wav">
                                <source src="{{ $fileUrl }}" type="audio/ogg">
                                {{ ui_t('pages.versions.audio_not_supported') }}
                            </audio>
                            <br>
                            <a href="{{ $fileUrl }}" class="btn btn-info" download>
                                <i class="fas fa-download"></i> {{ ui_t('pages.versions.download') }}
                            </a>
                        </div>
                    </div>
                @else
                    <div class="d-flex align-items-center justify-content-center h-100">
                        <div class="text-center">
                            <i class="fas fa-file fa-5x text-secondary mb-3"></i>
                            <h5>{{ ui_t('pages.versions.not_available') }}</h5>
                            <p class="text-muted">{{ $document->title }}</p>
                            <a href="{{ $fileUrl }}" class="btn btn-secondary" download>
                                <i class="fas fa-download"></i> {{ ui_t('pages.versions.download') }}
                            </a>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Metadata sidebar - hidden in destruction preview context --}}
            @unless($isDestructionContext)
            <div class="col-md-6">
                <div class="form-box shadow-sm bg-white h-100">
                    <form method="post" action="{{ route('documents.update', ['document' => $document->id]) }}">
                        @csrf
                        @method('PUT')

                        <div class="mt-4 p-4">
                            @php $isExpired = $document->expire_at && $document->expire_at->isPast(); @endphp
                            <label for="fileName" class="form-label">{{ ui_t('pages.versions.file_name') }}</label>
                            <input
                                type="text"
                                class="form-control"
                                id="fileName"
                                name="title"
                                placeholder="{{ ui_t('pages.versions.file_name') }}"
                                value="{{ old('title', $document->title ?? '') }}"
                                @if($isExpired) disabled @endif
                            />
                        </div>

                        <div class="row g-3 mb-3 p-4">
                            {{-- Pole (Department) - locked --}}
                            <div class="col-md-6">
                                <label for="pole" class="form-label">{{ __('Pole') }}</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="pole"
                                    value="{{ $document->service->subDepartment->department->name ?? 'N/A' }}"
                                    disabled
                                />
                            </div>

                            {{-- Department (Sub-Department) - locked --}}
                            <div class="col-md-6">
                                <label for="department" class="form-label">{{ __('Department') }}</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="department"
                                    value="{{ $document->service->subDepartment->name ?? 'N/A' }}"
                                    disabled
                                />
                            </div>

                            {{-- Service - locked --}}
                            <div class="col-md-6">
                                <label for="service" class="form-label">{{ __('Service') }}</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="service"
                                    value="{{ $document->service->name ?? 'N/A' }}"
                                    disabled
                                />
                            </div>

                            {{-- Category - editable, filtered by service --}}
                            <div class="col-md-6">
                                <label for="category" class="form-label">{{ __('Category') }}</label>
                                <select name="category_id" id="category" class="form-select" @if($isExpired) disabled @endif>
                                    <option value="">{{ __('-- Select Category --') }}</option>
                                    @if($document->service_id)
                                        @php
                                            $serviceCategories = $categories->where('service_id', $document->service_id);
                                        @endphp
                                        @forelse($serviceCategories as $category)
                                            <option value="{{ $category->id }}"
                                                {{ (old('category_id', $document->category_id ?? '') == $category->id) ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @empty
                                            <option value="" disabled>{{ __('No categories available for this service') }}</option>
                                        @endforelse
                                    @else
                                        <option value="" disabled>{{ __('Please assign a service first') }}</option>
                                    @endif
                                </select>
                            </div>

                            <input type="hidden" name="color" value="{{ old('color', $document->metadata['color'] ?? 'Blue') }}" />
                        </div>

                        <div class="row g-3 mb-3 p-4">
                            <div class="col-md-6">
                                <label for="creationDate" class="form-label">{{ ui_t('pages.versions.creation_date') }}</label>
                                <input
                                    type="date"
                                    class="form-control"
                                    id="creationDate"
                                    name="created_at"
                                    value="{{ old('created_at', optional($document->created_at)->format('Y-m-d')) }}"
                                    @if($isExpired) disabled @endif
                                />
                            </div>

                            <div class="col-md-6">
                                <label for="expire_at" class="form-label">{{ ui_t('pages.versions.expire_date') }}</label>
                                <input
                                    type="date"
                                    class="form-control"
                                    id="expire_at"
                                    name="expire_at"
                                    value="{{ old('expire_at', optional($document->expire_at)->format('Y-m-d')) }}"
                                />
                            </div>
                        </div>

                        <div class="row g-3 mb-3 p-4">
                            <div class="col-md-6">
                                <label for="submittedBy" class="form-label">{{ ui_t('pages.versions.submitted_by') }}</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="submittedBy"
                                    value="{{ $doc->uploadedBy?->full_name }}"
                                    disabled
                                />
                            </div>

                            <div class="col-md-6">
                                <label for="tags" class="form-label">{{ ui_t('tables.tags') }}</label>
                                <select class="form-select" id="tags" name="tags[]" multiple @if($isExpired) disabled @endif>
                                    @foreach($tags as $tag)
                                        <option value="{{ $tag->id }}"
                                            {{ in_array($tag->id, $document->tags->pluck('id')->toArray() ?? []) ? 'selected' : '' }}>
                                            {{ $tag->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="p-4">
                            <label class="form-label">{{ ui_t('pages.versions.physical_location') }}</label>
                            <div class="row g-2">
                                <div class="col-md-3">
                                    <label class="form-label small">{{ ui_t('pages.versions.room') }}</label>
                                    <select class="form-select form-select-sm" id="preview_room_id" name="room_id">
                                        <option value="">{{ ui_t('pages.versions.select_room') }}</option>
                                        @foreach($rooms as $room)
                                            <option value="{{ $room->id }}"
                                                @if($document->box && $document->box->shelf->row->room->id == $room->id) selected @endif>
                                                {{ $room->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small">{{ ui_t('pages.versions.row') }}</label>
                                    <select class="form-select form-select-sm" id="preview_row_id" name="row_id" disabled>
                                        <option value="">{{ ui_t('pages.versions.select_row') }}</option>
                                        @if($document->box)
                                            @foreach($document->box->shelf->row->room->rows as $row)
                                                <option value="{{ $row->id }}"
                                                    @if($document->box->shelf->row->id == $row->id) selected @endif>
                                                    {{ $row->name }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small">{{ ui_t('pages.versions.shelf') }}</label>
                                    <select class="form-select form-select-sm" id="preview_shelf_id" name="shelf_id" disabled>
                                        <option value="">{{ ui_t('pages.versions.select_shelf') }}</option>
                                        @if($document->box)
                                            @foreach($document->box->shelf->row->shelves as $shelf)
                                                <option value="{{ $shelf->id }}"
                                                    @if($document->box->shelf->id == $shelf->id) selected @endif>
                                                    {{ $shelf->name }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small">{{ ui_t('pages.versions.box') }} <span class="text-danger">*</span></label>
                                    <select class="form-select form-select-sm" id="preview_box_id" name="box_id" required disabled>
                                        <option value="">{{ ui_t('pages.versions.select_box') }}</option>
                                        @if($document->box)
                                            @foreach($document->box->shelf->boxes as $box)
                                                <option value="{{ $box->id }}"
                                                    @if($document->box_id == $box->id) selected @endif>
                                                    {{ $box->name }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                            @if($document->box)
                                <small class="text-muted mt-2 d-block">{{ ui_t('pages.versions.current') }} <code>{{ $document->box->__toString() }}</code></small>
                            @endif
                        </div>

                        <div class="d-flex justify-content-end gap-3 p-2">
                            @if(($document->status ?? null) === 'pending')
                                @can('decline',$document)
                                    <button
                                        class="btn btn-outline-danger  trigger-action"
                                        data-id="{{ $document->id }}"
                                        data-name="{{ $document->title }}"
                                        data-url="{{ route('documents.decline', $document->id) }}"
                                        data-method="PUT"
                                        data-button-text="{{ ui_t('pages.versions.confirm') }}"
                                        data-title="{{ ui_t('pages.versions.reject_title') }}"
                                        data-body="{{ ui_t('pages.versions.reject_body') }}"
                                    >
                                        {{ ui_t('pages.versions.reject') }}
                                    </button>
                                @endcan
                                @can('approve',$document)
                                        <button
                                            class="btn btn-outline-success  trigger-action"
                                            data-id="{{ $document->id }}"
                                            data-name="{{ $document->title }}"
                                            data-url="{{ route('documents.approve', $document->id) }}"
                                            data-method="PUT"
                                            data-button-text="{{ ui_t('pages.versions.confirm') }}"
                                            data-title="{{ ui_t('pages.versions.approve_title') }}"
                                            data-body="{{ ui_t('pages.versions.approve_body') }}"
                                            data-icon="{{ asset('assets/mage_question-mark-circle.png') }}"
                                            data-button-class="btn-success"
                                        >
                                            {{ ui_t('pages.versions.approve') }}
                                        </button>
                                @endcan
                            @endif
                            @can('update',$document)
                                <button type="submit" class="btn py-2 px-4 btn-outline-primary">{{ ui_t('pages.versions.modify') }}</button>
                            @endcan

                        </div>
                    </form>
                </div>
            </div>
            @endunless
        </div>
    </div>
        @include('components.modals.confirm-modal')

    <script>
        const previewRooms = @json($rooms);
        const currentBoxId = @json($document->box_id ?? null);
        const translations = {
            selectRow: @json(ui_t('pages.physical.selects.select_row')),
            firstSelectRow: @json(ui_t('pages.physical.selects.first_select_row')),
            selectShelf: @json(ui_t('pages.physical.selects.select_shelf')),
            firstSelectShelf: @json(ui_t('pages.physical.selects.first_select_shelf')),
            selectBox: @json(ui_t('pages.physical.selects.select_box')),
        };
        
        const roomSelect = document.getElementById('preview_room_id');
        const rowSelect = document.getElementById('preview_row_id');
        const shelfSelect = document.getElementById('preview_shelf_id');
        const boxSelect = document.getElementById('preview_box_id');
        
        if (roomSelect) {
            // If document has a box, enable row dropdown
            if (currentBoxId && roomSelect.value) {
                updatePreviewRows(parseInt(roomSelect.value));
            }
            
            roomSelect.addEventListener('change', function() {
                const roomId = parseInt(this.value);
                updatePreviewRows(roomId);
                shelfSelect.innerHTML = '<option value="">' + translations.firstSelectRow + '</option>';
                shelfSelect.disabled = true;
                boxSelect.innerHTML = '<option value="">' + translations.firstSelectShelf + '</option>';
                boxSelect.disabled = true;
            });
        }
        
        function updatePreviewRows(roomId) {
            rowSelect.innerHTML = '<option value="">' + translations.selectRow + '</option>';
            
            if (!roomId) return;
            
            const room = previewRooms.find(r => r.id === roomId);
            if (room) {
                room.rows.forEach(row => {
                    const option = document.createElement('option');
                    option.value = row.id;
                    option.textContent = row.name;
                    rowSelect.appendChild(option);
                });
                rowSelect.disabled = false;
            }
        }
        
        if (rowSelect) {
            rowSelect.addEventListener('change', function() {
                const roomId = parseInt(roomSelect.value);
                const rowId = parseInt(this.value);
                updatePreviewShelves(roomId, rowId);
                boxSelect.innerHTML = '<option value="">' + translations.firstSelectShelf + '</option>';
                boxSelect.disabled = true;
            });
        }
        
        function updatePreviewShelves(roomId, rowId) {
            shelfSelect.innerHTML = '<option value="">' + translations.selectShelf + '</option>';
            
            if (!roomId || !rowId) return;
            
            const room = previewRooms.find(r => r.id === roomId);
            if (room) {
                const row = room.rows.find(r => r.id === rowId);
                if (row) {
                    row.shelves.forEach(shelf => {
                        const option = document.createElement('option');
                        option.value = shelf.id;
                        option.textContent = shelf.name;
                        shelfSelect.appendChild(option);
                    });
                    shelfSelect.disabled = false;
                }
            }
        }
        
        if (shelfSelect) {
            shelfSelect.addEventListener('change', function() {
                const roomId = parseInt(roomSelect.value);
                const rowId = parseInt(rowSelect.value);
                const shelfId = parseInt(this.value);
                updatePreviewBoxes(roomId, rowId, shelfId);
            });
        }
        
        function updatePreviewBoxes(roomId, rowId, shelfId) {
            boxSelect.innerHTML = '<option value="">' + translations.selectBox + '</option>';
            
            if (!roomId || !rowId || !shelfId) return;
            
            const room = previewRooms.find(r => r.id === roomId);
            if (room) {
                const row = room.rows.find(r => r.id === rowId);
                if (row) {
                    const shelf = row.shelves.find(s => s.id === shelfId);
                    if (shelf) {
                        shelf.boxes.forEach(box => {
                            const option = document.createElement('option');
                            option.value = box.id;
                            option.textContent = box.name;
                            if (currentBoxId && box.id === currentBoxId) {
                                option.selected = true;
                            }
                            boxSelect.appendChild(option);
                        });
                        boxSelect.disabled = false;
                    }
                }
            }
        }
    </script>
@endsection
