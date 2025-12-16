<!-- Move Document Modal -->
<div class="modal fade" id="moveDocumentModal-{{ $doc->id }}" tabindex="-1" aria-labelledby="moveDocumentLabel-{{ $doc->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form method="POST" action="{{ route('document-movements.store', $doc->id) }}">
            @csrf
            <input type="hidden" name="document_id" value="{{ $doc->id }}">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="moveDocumentLabel-{{ $doc->id }}">{{ ui_t('pages.documents.move.title') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ ui_t('actions.close') }}"></button>
                </div>

                <div class="modal-body">
                    <p>{{ ui_t('pages.documents.move.description') }}</p>

                    <div class="mt-3">
                        <label for="movement_type">{{ ui_t('pages.documents.move.movement_type') }}</label>
                        <select name="movement_type" class="form-control" required>
                            <option value="">{{ ui_t('pages.documents.move.select_type') }}</option>
                            @foreach(['storage', 'retrieval', 'transfer'] as $type)
                                <option value="{{ $type }}" {{ old('movement_type') == $type ? 'selected' : '' }}>
                                    {{ ui_t('pages.documents.move.movement_type_options.' . $type) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Moved From (Current Location) -->
                    <div class="mt-3">
                        <label class="form-label">{{ ui_t('pages.documents.move.moved_from') }}</label>
                        @if($document->box)
                            <input type="text" class="form-control" value="{{ $document->box->__toString() }}" readonly 
                                   style="background-color: #f8f9fa;">
                            <input type="hidden" name="moved_from_box_id" value="{{ $document->box_id }}">
                            <small class="text-muted">{{ ui_t('pages.documents.move.current_location_hint') }}</small>
                        @else
                            <input type="text" class="form-control" value="{{ ui_t('pages.documents.move.no_location_assigned') }}" readonly 
                                   style="background-color: #f8f9fa;">
                            <small class="text-danger">{{ ui_t('pages.documents.move.no_location_hint') }}</small>
                        @endif
                    </div>

                    <!-- Moved To (New Location) - Hierarchical Selection -->
                    <div class="mt-3">
                        <label class="form-label">{{ ui_t('pages.documents.move.moved_to') }} <span class="text-danger">*</span></label>
                        <div class="row g-2">
                            <div class="col-md-3">
                                <label class="form-label small">{{ ui_t('pages.documents.move.room') }}</label>
                                <select class="form-select form-select-sm" id="move_room_id_{{ $doc->id }}" name="move_room_id">
                                    <option value="">{{ ui_t('pages.documents.move.select_room') }}</option>
                                    @foreach($rooms ?? \App\Models\Room::with('rows.shelves.boxes')->get() as $room)
                                        <option value="{{ $room->id }}">
                                            {{ $room->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">{{ ui_t('pages.documents.move.row') }}</label>
                                <select class="form-select form-select-sm" id="move_row_id_{{ $doc->id }}" name="move_row_id" disabled>
                                    <option value="">{{ ui_t('pages.documents.move.first_select_room') }}</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">{{ ui_t('pages.documents.move.shelf') }}</label>
                                <select class="form-select form-select-sm" id="move_shelf_id_{{ $doc->id }}" name="move_shelf_id" disabled>
                                    <option value="">{{ ui_t('pages.documents.move.first_select_row') }}</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">{{ ui_t('pages.documents.move.box') }} <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm" id="move_box_id_{{ $doc->id }}" name="moved_to_box_id" required disabled>
                                    <option value="">{{ ui_t('pages.documents.move.first_select_shelf') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-dark">{{ ui_t('pages.documents.move.confirm') }}</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ ui_t('pages.documents.move.cancel') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const rooms = @json($rooms ?? \App\Models\Room::with('rows.shelves.boxes')->get());
    const docId = {{ $doc->id }};
    
    const roomSelect = document.getElementById('move_room_id_' + docId);
    const rowSelect = document.getElementById('move_row_id_' + docId);
    const shelfSelect = document.getElementById('move_shelf_id_' + docId);
    const boxSelect = document.getElementById('move_box_id_' + docId);
    
    if (!roomSelect) return;
    
    roomSelect.addEventListener('change', function() {
        const roomId = parseInt(this.value);
        rowSelect.innerHTML = '<option value="">{{ ui_t('pages.documents.move.first_select_room') }}</option>';
        shelfSelect.innerHTML = '<option value="">{{ ui_t('pages.documents.move.first_select_row') }}</option>';
        shelfSelect.disabled = true;
        boxSelect.innerHTML = '<option value="">{{ ui_t('pages.documents.move.first_select_shelf') }}</option>';
        boxSelect.disabled = true;
        
        if (!roomId) return;
        
        const room = rooms.find(r => r.id === roomId);
        if (room) {
            room.rows.forEach(row => {
                const option = document.createElement('option');
                option.value = row.id;
                option.textContent = row.name;
                rowSelect.appendChild(option);
            });
            rowSelect.disabled = false;
        }
    });
    
    rowSelect.addEventListener('change', function() {
        const roomId = parseInt(roomSelect.value);
        const rowId = parseInt(this.value);
        shelfSelect.innerHTML = '<option value="">{{ ui_t('pages.documents.move.shelf') }}</option>';
        boxSelect.innerHTML = '<option value="">{{ ui_t('pages.documents.move.first_select_shelf') }}</option>';
        boxSelect.disabled = true;
        
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
                shelfSelect.disabled = false;
            }
        }
    });
    
    shelfSelect.addEventListener('change', function() {
        const roomId = parseInt(roomSelect.value);
        const rowId = parseInt(rowSelect.value);
        const shelfId = parseInt(this.value);
        boxSelect.innerHTML = '<option value="">{{ ui_t('pages.documents.move.box') }}</option>';
        
        if (!roomId || !rowId || !shelfId) return;
        
        const room = rooms.find(r => r.id === roomId);
        if (room) {
            const row = room.rows.find(r => r.id === rowId);
            if (row) {
                const shelf = row.shelves.find(s => s.id === shelfId);
                if (shelf) {
                    shelf.boxes.forEach(box => {
                        // Don't show current box if document is already in a box
                        @if($document->box)
                            if (box.id !== {{ $document->box_id }}) {
                        @endif
                            const option = document.createElement('option');
                            option.value = box.id;
                            option.textContent = box.name;
                            boxSelect.appendChild(option);
                        @if($document->box)
                            }
                        @endif
                    });
                    boxSelect.disabled = false;
                }
            }
        }
    });
});
</script>
