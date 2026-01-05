@extends('layouts.app')

@section('content')
    <div class="addlocation w-75 mt-5 position-relative">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3>{{ ui_t('pages.physical.title') }}</h3>
            @can('viewAny', \App\Models\PhysicalLocation::class)
                <a href="{{ route('physical-locations.export') }}" class="btn btn-outline-dark">{{ ui_t('pages.physical.export_report') }}</a>
            @endcan
        </div>



        @can('create', \App\Models\PhysicalLocation::class)
            {{-- Quick Create Form hidden as per requirements
            <div class="card mb-4">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-plus-circle"></i> {{ ui_t('pages.physical.quick_create') }}</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">{{ ui_t('pages.physical.quick_create_help') }}</p>
                    <form method="post" action="{{ route('physical-locations.store') }}">
                        @csrf
                        <!-- original quick-create fields removed -->
                    </form>
                </div>
            </div>
            --}}

            <!-- Add Individual Items -->
            <div class="row g-3 mb-4">
                <!-- Add New Room (first card) -->
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header bg-secondary text-white">
                            <h6 class="mb-0"><i class="fas fa-plus"></i> {{ ui_t('pages.physical.actions.add_new_room') }}</h6>
                        </div>
                        <div class="card-body">
                            <form method="post" action="{{ route('physical-locations.add-room') }}">
                                @csrf
                                <div class="mb-3">
                                    <label for="add_room_name" class="form-label">{{ ui_t('pages.physical.actions.room_name') }} <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="add_room_name" name="name" 
                                           placeholder="{{ ui_t('pages.physical.placeholders.room_example') }}" required>
                                </div>
                                <div class="mb-3">
                                    <label for="add_room_description" class="form-label">{{ ui_t('pages.physical.fields.description_optional') }}</label>
                                    <textarea class="form-control" id="add_room_description" name="description" rows="2"></textarea>
                                </div>
                                <button type="submit" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-plus"></i> {{ ui_t('pages.physical.actions.add_room') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Add Row to Room (second card) -->
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0"><i class="fas fa-plus"></i> {{ ui_t('pages.physical.actions.add_row_to_room') }}</h6>
                        </div>
                        <div class="card-body">
                            <form method="post" action="{{ route('physical-locations.add-row') }}">
                                @csrf
                                <div class="mb-3">
                                    <label for="add_row_room_id" class="form-label">{{ ui_t('pages.physical.actions.select_room') }} <span class="text-danger">*</span></label>
                                    <select class="form-select" id="add_row_room_id" name="room_id" required>
                                        <option value="">{{ ui_t('pages.physical.selects.select_room') }}</option>
                                        @foreach(\App\Models\Room::all() as $room)
                                            <option value="{{ $room->id }}">{{ $room->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="add_row_name" class="form-label">{{ ui_t('pages.physical.actions.row_name') }} <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="add_row_name" name="name" 
                                           placeholder="{{ ui_t('pages.physical.placeholders.row_example') }}" required>
                                </div>
                                <div class="mb-3">
                                                <label for="add_row_description" class="form-label">{{ ui_t('pages.physical.fields.description_optional') }}</label>
                                    <textarea class="form-control" id="add_row_description" name="description" rows="2"></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus"></i> {{ ui_t('pages.physical.actions.add_row') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Add Shelf to Row -->
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="fas fa-plus"></i> {{ ui_t('pages.physical.actions.add_shelf_to_row') }}</h6>
                        </div>
                        <div class="card-body">
                            <form method="post" action="{{ route('physical-locations.add-shelf') }}">
                                @csrf
                                <div class="mb-3">
                                    <label for="add_shelf_room_id" class="form-label">{{ ui_t('pages.physical.actions.select_room') }}</label>
                                    <select class="form-select" id="add_shelf_room_id" name="room_id">
                                        <option value="">{{ ui_t('pages.physical.selects.select_room') }}</option>
                                        @foreach(\App\Models\Room::all() as $room)
                                            <option value="{{ $room->id }}">{{ $room->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="add_shelf_row_id" class="form-label">{{ ui_t('pages.physical.actions.select_row') }} <span class="text-danger">*</span></label>
                                    <select class="form-select" id="add_shelf_row_id" name="row_id" required>
                                        <option value="">{{ ui_t('pages.physical.selects.first_select_room') }}</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="add_shelf_name" class="form-label">{{ ui_t('pages.physical.actions.shelf_name') }} <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="add_shelf_name" name="name" 
                                           placeholder="{{ ui_t('pages.physical.placeholders.shelf_example') }}" required>
                                </div>
                                <div class="mb-3">
                                    <label for="add_shelf_description" class="form-label">{{ ui_t('pages.physical.fields.description_optional') }}</label>
                                    <textarea class="form-control" id="add_shelf_description" name="description" rows="2"></textarea>
                                </div>
                                <button type="submit" class="btn btn-info btn-sm">
                                    <i class="fas fa-plus"></i> {{ ui_t('pages.physical.actions.add_shelf') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Add Box to Shelf (fourth card, second row right) -->
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header bg-success text-white">
                            <h6 class="mb-0"><i class="fas fa-plus"></i> {{ ui_t('pages.physical.actions.add_box_to_shelf') }}</h6>
                        </div>
                        <div class="card-body">
                            <form method="post" action="{{ route('physical-locations.add-box') }}">
                                @csrf
                                
                                {{-- Service Selection - Required First --}}
                                <div class="mb-3">
                                    <label for="add_box_service_id" class="form-label">{{ __('Service') }} <span class="text-danger">*</span></label>
                                    <select class="form-select" id="add_box_service_id" name="service_id" required>
                                        <option value="">{{ __('Select Service') }}</option>
                                        @php
                                            $user = auth()->user();
                                            $accessibleServiceIds = \App\Models\Box::getAccessibleServiceIds($user);
                                            
                                            // Get accessible services
                                            if ($accessibleServiceIds === 'all') {
                                                $services = \App\Models\Service::orderBy('name')->get();
                                            } else {
                                                $services = \App\Models\Service::whereIn('id', $accessibleServiceIds)->orderBy('name')->get();
                                            }
                                        @endphp
                                        @foreach($services as $service)
                                            <option value="{{ $service->id }}">{{ $service->name }}</option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">{{ __('This box will be linked to the selected service') }}</small>
                                </div>

                                <div class="mb-3">
                                    <label for="add_box_room_id" class="form-label">{{ ui_t('pages.physical.actions.select_room') }}</label>
                                    <select class="form-select" id="add_box_room_id" name="room_id">
                                        <option value="">{{ ui_t('pages.physical.selects.select_room') }}</option>
                                        @foreach(\App\Models\Room::all() as $room)
                                            <option value="{{ $room->id }}">{{ $room->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="add_box_row_id" class="form-label">{{ ui_t('pages.physical.actions.select_row') }}</label>
                                    <select class="form-select" id="add_box_row_id" name="row_id">
                                        <option value="">{{ ui_t('pages.physical.selects.first_select_room') }}</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="add_box_shelf_id" class="form-label">{{ ui_t('pages.physical.selects.select_shelf') }} <span class="text-danger">*</span></label>
                                    <select class="form-select" id="add_box_shelf_id" name="shelf_id" required>
                                        <option value="">{{ ui_t('pages.physical.selects.first_select_row') }}</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="add_box_name" class="form-label">{{ ui_t('pages.physical.actions.box_name') }} <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="add_box_name" name="name" 
                                           placeholder="{{ ui_t('pages.physical.placeholders.box_example') }}" required>
                                </div>
                                <div class="mb-3">
                                    <label for="add_box_description" class="form-label">{{ ui_t('pages.physical.fields.description_optional') }}</label>
                                    <textarea class="form-control" id="add_box_description" name="description" rows="2"></textarea>
                                </div>
                                <button type="submit" class="btn btn-success btn-sm">
                                    <i class="fas fa-plus"></i> {{ ui_t('pages.physical.actions.add_box') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endcan

        <!-- Hierarchical Tree View -->
        <div class="mt-4">
            <h5 class="mb-3">{{ ui_t('pages.physical.actions.location_structure') }}</h5>
            
            @if(isset($rooms) && $rooms->count() > 0)
                @foreach($rooms as $room)
                    @php
                        // Check if room has any visible boxes (after service filtering)
                        $hasVisibleBoxes = $room->rows->some(function($row) {
                            return $row->shelves->some(function($shelf) {
                                return $shelf->boxes->isNotEmpty();
                            });
                        });
                    @endphp
                    
                    @if($hasVisibleBoxes)
                    <div class="card mb-3">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <div>
                                <strong>📍 {{ ui_t('pages.physical.fields.room') }}: {{ $room->name }}</strong>
                                @if($room->description)
                                    <small class="d-block">{{ $room->description }}</small>
                                @endif
                            </div>
                            @php
                                // Count only rows that have shelves with boxes
                                $visibleRowsCount = $room->rows->filter(function($row) {
                                    return $row->shelves->some(function($shelf) {
                                        return $shelf->boxes->isNotEmpty();
                                    });
                                })->count();
                            @endphp
                            <span class="badge bg-light text-dark">{{ $visibleRowsCount }} {{ ui_t('pages.physical.fields.row') }}(s)</span>
                        </div>
                        <div class="card-body">
                            @if($room->rows->count() > 0)
                                @foreach($room->rows as $row)
                                    @php
                                        // Check if row has any shelves with boxes
                                        $hasVisibleShelves = $row->shelves->some(function($shelf) {
                                            return $shelf->boxes->isNotEmpty();
                                        });
                                    @endphp
                                    
                                    @if($hasVisibleShelves)
                                    <div class="ms-3 mb-3 border-start border-3 border-secondary ps-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div>
                                                <strong>📐 {{ ui_t('pages.physical.fields.row') }}: {{ $row->name }}</strong>
                                                @if($row->description)
                                                    <small class="text-muted d-block">{{ $row->description }}</small>
                                                @endif
                                            </div>
                                            @php
                                                // Count only shelves that have boxes
                                                $visibleShelvesCount = $row->shelves->filter(function($shelf) {
                                                    return $shelf->boxes->isNotEmpty();
                                                })->count();
                                            @endphp
                                            <span class="badge bg-secondary">{{ $visibleShelvesCount }} {{ ui_t('pages.physical.fields.shelf') }}(s)</span>
                                        </div>
                                        
                                        @if($row->shelves->count() > 0)
                                            @foreach($row->shelves as $shelf)
                                                <div class="ms-3 mt-2 mb-2 border-start border-2 border-info ps-3">
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <div>
                                                            <strong>📚 {{ ui_t('pages.physical.fields.shelf') }}: {{ $shelf->name }}</strong>
                                                            @if($shelf->description)
                                                                <small class="text-muted d-block">{{ $shelf->description }}</small>
                                                            @endif
                                                        </div>
                                                        <span class="badge bg-info text-dark">{{ $shelf->boxes->count() }} {{ ui_t('pages.physical.fields.box') }}(es)</span>
                                                    </div>
                                                    
                                                    <ul class="list-unstyled ms-3 mt-2">
                                                            @foreach($shelf->boxes as $box)
                                                                <li class="mb-2 p-2 bg-light rounded d-flex justify-content-between align-items-center">
                                                                    <div>
                                                                        <strong>📦 {{ $box->name }}</strong>
                                                                        @if($box->description)
                                                                            <small class="text-muted d-block">{{ $box->description }}</small>
                                                                        @endif
                                                                        <small class="text-muted">
                                                                            {{ ui_t('pages.physical.actions.full_path') }} <code>{{ $box->__toString() }}</code>
                                                                        </small>
                                                                        @if($box->documents->count() > 0)
                                                                            <a href="{{ route('documents.all', ['box_id' => $box->id]) }}" 
                                                                               class="badge bg-secondary ms-2 text-decoration-none"
                                                                               data-bs-toggle="tooltip" 
                                                                               data-bs-html="true"
                                                                               data-bs-placement="top"
                                                                               title="<div class='text-start'><strong>{{ __('Click to view documents') }}</strong><ul class='list-unstyled mb-0 mt-1'>@foreach($box->documents->take(5) as $doc)<li>• {{ $doc->title }}</li>@endforeach @if($box->documents->count() > 5)<li class='text-muted fst-italic'>{{ __('And :count more...', ['count' => $box->documents->count() - 5]) }}</li>@endif</ul></div>">
                                                                                {{ $box->documents->count() }} {{ __('file(s)') }}
                                                                            </a>
                                                                        @else
                                                                            <span class="badge bg-secondary ms-2">0 {{ __('file(s)') }}</span>
                                                                        @endif
                                                                    </div>
                                                                    <div class="d-inline-flex gap-1">
                                                                        @can('create', \App\Models\PhysicalLocation::class)
                                                                            <button class="btn btn-sm btn-outline-primary" type="button" 
                                                                                    data-bs-toggle="modal" data-bs-target="#editBoxModal{{ $box->id }}">
                                                                                <i class="fas fa-edit"></i> {{ ui_t('pages.physical.actions.edit') }}
                                                                            </button>
                                                                        @endcan
                                                                        @can('delete physical location')
                                                                            <form method="POST" action="{{ route('physical-locations.destroy-box', $box->id) }}" 
                                                                                  class="d-inline" onsubmit="return confirm('{{ ui_t('pages.activity_log.are_you_sure') }}');">
                                                                                @csrf
                                                                                @method('DELETE')
                                                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                                                    <i class="fas fa-trash"></i> {{ ui_t('pages.physical.actions.delete') }}
                                                                                </button>
                                                                            </form>
                                                                        @endcan
                                                                    </div>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    @else
                                                        <p class="text-muted small ms-3"><em>{{ ui_t('pages.physical.actions.no_boxes') }}</em></p>
                                                    @endif
                                                </div>
                                                @endif
                                            @endforeach
                                        @else
                                            <p class="text-muted small ms-3"><em>{{ ui_t('pages.physical.actions.no_shelves') }}</em></p>
                                        @endif
                                    </div>
                                    @endif
                                @endforeach
                            @else
                                <p class="text-muted ms-3"><em>{{ ui_t('pages.physical.actions.no_rows') }}</em></p>
                            @endif
                        </div>
                    </div>
                    @endif
                @endforeach
            @else
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    <strong>{{ ui_t('pages.physical.actions.no_locations') }}</strong> {{ ui_t('pages.physical.actions.use_forms') }}
                </div>
            @endif
        </div>
    </div>
    @include('components.modals.confirm-modal')

    <!-- Edit Box Modal -->
    @if(isset($rooms))
        @foreach($rooms as $room)
            @foreach($room->rows as $row)
                @foreach($row->shelves as $shelf)
                    @foreach($shelf->boxes as $box)
                        <div class="modal fade" id="editBoxModal{{ $box->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('physical-locations.update-box', $box->id) }}">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-header">
                                            <h5 class="modal-title">{{ ui_t('pages.physical.actions.edit') }} {{ ui_t('pages.physical.fields.box') }}: {{ $box->name }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label for="edit_room_name_{{ $box->id }}" class="form-label">{{ ui_t('pages.physical.fields.room') }}</label>
                                                <input type="text" class="form-control edit-box-room-name"
                                                       id="edit_room_name_{{ $box->id }}"
                                                       data-box-id="{{ $box->id }}"
                                                       name="room_name"
                                                       value="{{ $room->name }}" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="edit_row_name_{{ $box->id }}" class="form-label">{{ ui_t('pages.physical.fields.row') }}</label>
                                                <input type="text" class="form-control edit-box-row-name"
                                                       id="edit_row_name_{{ $box->id }}"
                                                       data-box-id="{{ $box->id }}"
                                                       name="row_name"
                                                       value="{{ $row->name }}" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="edit_shelf_name_{{ $box->id }}" class="form-label">{{ ui_t('pages.physical.fields.shelf') }}</label>
                                                <input type="text" class="form-control edit-box-shelf-name"
                                                       id="edit_shelf_name_{{ $box->id }}"
                                                       data-box-id="{{ $box->id }}"
                                                       name="shelf_name"
                                                       value="{{ $shelf->name }}" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="box_name_edit{{ $box->id }}" class="form-label">{{ ui_t('pages.physical.actions.box_name') }}</label>
                                                <input type="text" class="form-control" id="box_name_edit{{ $box->id }}" 
                                                       name="name" value="{{ $box->name }}" required>
                                            </div>

                                            {{-- Service Selection - After Box Name --}}
                                            <div class="mb-3">
                                                <label for="edit_service_id_{{ $box->id }}" class="form-label">{{ __('Service') }} <span class="text-danger">*</span></label>
                                                <select class="form-select" id="edit_service_id_{{ $box->id }}" name="service_id" required>
                                                    @php
                                                        $user = auth()->user();
                                                        $accessibleServiceIds = \App\Models\Box::getAccessibleServiceIds($user);
                                                        
                                                        // Get accessible services
                                                        if ($accessibleServiceIds === 'all') {
                                                            $editServices = \App\Models\Service::orderBy('name')->get();
                                                        } else {
                                                            $editServices = \App\Models\Service::whereIn('id', $accessibleServiceIds)->orderBy('name')->get();
                                                        }
                                                    @endphp
                                                    @foreach($editServices as $service)
                                                        <option value="{{ $service->id }}" {{ $box->service_id == $service->id ? 'selected' : '' }}>
                                                            {{ $service->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="mb-3">
                                                <label for="box_description_edit{{ $box->id }}" class="form-label">{{ ui_t('pages.physical.fields.description_optional') }}</label>
                                                <textarea class="form-control" id="box_description_edit{{ $box->id }}" 
                                                          name="description" rows="2">{{ $box->description }}</textarea>
                                            </div>
                                            <p class="text-muted small">
                                                <strong>{{ ui_t('pages.physical.actions.full_path') }}</strong>
                                                <span id="edit_box_full_path_{{ $box->id }}">{{ $box->__toString() }}</span>
                                            </p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ ui_t('pages.physical.actions.cancel') }}</button>
                                            <button type="submit" class="btn btn-primary">{{ ui_t('pages.physical.actions.update') }}</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endforeach
            @endforeach
        @endforeach
    @endif

    <script>
        const rooms = @json(\App\Models\Room::with('rows.shelves')->get());
        const translations = {
            selectRow: @json(ui_t('pages.physical.selects.select_row')),
            firstSelectRow: @json(ui_t('pages.physical.selects.first_select_row')),
            selectShelf: @json(ui_t('pages.physical.selects.select_shelf')),
            firstSelectShelf: @json(ui_t('pages.physical.selects.first_select_shelf')),
        };

        // Helper to update full path text inside edit modal
        function updateEditBoxFullPath(boxId) {
            const roomInput = document.getElementById('edit_room_name_' + boxId);
            const rowInput = document.getElementById('edit_row_name_' + boxId);
            const shelfInput = document.getElementById('edit_shelf_name_' + boxId);
            const boxNameInput = document.getElementById('box_name_edit' + boxId);
            const pathSpan = document.getElementById('edit_box_full_path_' + boxId);
            if (!roomInput || !rowInput || !shelfInput || !boxNameInput || !pathSpan) return;

            const parts = [];
            const roomText = roomInput.value.trim();
            const rowText = rowInput.value.trim();
            const shelfText = shelfInput.value.trim();
            const boxText = boxNameInput.value.trim();
            if (roomText) parts.push(roomText);
            if (rowText) parts.push(rowText);
            if (shelfText) parts.push(shelfText);
            if (boxText) parts.push(boxText);
            // Use arrow separator between path segments
            pathSpan.textContent = parts.join(' → ');
        }

        // Initialize edit box full path updates based on text inputs
        document.addEventListener('DOMContentLoaded', function () {
            // Initialize Bootstrap tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            document.querySelectorAll('.edit-box-room-name').forEach(function (roomInput) {
                const boxId = roomInput.dataset.boxId;
                const rowInput = document.getElementById('edit_row_name_' + boxId);
                const shelfInput = document.getElementById('edit_shelf_name_' + boxId);
                const boxNameInput = document.getElementById('box_name_edit' + boxId);

                function attach(el) {
                    if (!el) return;
                    el.addEventListener('input', function () {
                        updateEditBoxFullPath(boxId);
                    });
                }

                attach(roomInput);
                attach(rowInput);
                attach(shelfInput);
                attach(boxNameInput);

                updateEditBoxFullPath(boxId);
            });
        });

        // Add Row to Room - Simple, no cascading needed

        // Add Shelf to Row - Cascade from Room to Row
        document.getElementById('add_shelf_room_id')?.addEventListener('change', function() {
            const roomId = parseInt(this.value);
            const rowSelect = document.getElementById('add_shelf_row_id');
            rowSelect.innerHTML = '<option value="">' + translations.selectRow + '</option>';
            
            if (!roomId) return;
            
            const room = rooms.find(r => r.id === roomId);
            if (room) {
                room.rows.forEach(row => {
                    const option = document.createElement('option');
                    option.value = row.id;
                    option.textContent = row.name;
                    rowSelect.appendChild(option);
                });
            }
        });

        // Add Box to Shelf - Cascade from Room to Row to Shelf
        document.getElementById('add_box_room_id')?.addEventListener('change', function() {
            const roomId = parseInt(this.value);
            const rowSelect = document.getElementById('add_box_row_id');
            const shelfSelect = document.getElementById('add_box_shelf_id');
            
            rowSelect.innerHTML = '<option value="">' + translations.selectRow + '</option>';
            shelfSelect.innerHTML = '<option value="">' + translations.firstSelectRow + '</option>';
            
            if (!roomId) return;
            
            const room = rooms.find(r => r.id === roomId);
            if (room) {
                room.rows.forEach(row => {
                    const option = document.createElement('option');
                    option.value = row.id;
                    option.textContent = row.name;
                    rowSelect.appendChild(option);
                });
            }
        });

        document.getElementById('add_box_row_id')?.addEventListener('change', function() {
            const roomId = parseInt(document.getElementById('add_box_room_id').value);
            const rowId = parseInt(this.value);
            const shelfSelect = document.getElementById('add_box_shelf_id');
            shelfSelect.innerHTML = '<option value="">' + translations.selectShelf + '</option>';
            
            if (!roomId || !rowId) return;
            
            const room = rooms.find(r => r.id === roomId);
            if (room) {
                const row = room.rows.find(r => r.id === rowId);
                if (row) {
                    row.shelves.forEach(shelf => {
                        const option = document.createElement('option');
                        option.value = shelf.id;
                        option.textContent = shelf.name;
                        shelfSelect.appendChild(option);
                    });
                }
            }
        });
    </script>
@endsection
