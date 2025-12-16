@props([
    'location'
])
<div class="modal fade" id="editModal{{ $location->id }}" tabindex="-1" aria-labelledby="editModalLabel{{ $location->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="{{ route('physical-locations.update', $location->id) }}">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel{{ $location->id }}">{{ ui_t('pages.physical.actions.edit') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ ui_t('actions.close') }}"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="roomInput" class="form-label">{{ ui_t('pages.physical.fields.room') }}</label>
                            <input
                                type="text"
                                id="roomInput"
                                name="room"
                                class="form-control"
                                placeholder="{{ ui_t('pages.physical.placeholders.room_example') }}"
                                value="{{ old('room', $location->room) }}"
                                required
                                aria-describedby="roomHelp"
                            >
                            <div id="roomHelp" class="form-text">{{ ui_t('pages.physical.room_help') ?? '' }}</div>
                        </div>

                        <div class="col-md-6">
                            <label for="rowInput" class="form-label">{{ ui_t('pages.physical.fields.row') }}</label>
                            <input
                                type="text"
                                id="rowInput"
                                name="row"
                                class="form-control"
                                placeholder="{{ ui_t('pages.physical.row_placeholder') ?? '' }}"
                                value="{{ old('row', $location->row) }}"
                                required
                                aria-describedby="rowHelp"
                            >
                            <div id="rowHelp" class="form-text">{{ ui_t('pages.physical.row_help') ?? '' }}</div>
                        </div>

                        <div class="col-md-6">
                            <label for="shelfInput" class="form-label">{{ ui_t('pages.physical.fields.shelf') }}</label>
                            <input
                                type="number"
                                id="shelfInput"
                                name="shelf"
                                class="form-control"
                                placeholder="{{ ui_t('pages.physical.shelf_placeholder') ?? '' }}"
                                min="0"
                                value="{{ old('shelf', $location->shelf) }}"
                                required
                                aria-describedby="shelfHelp"
                            >
                            <div id="shelfHelp" class="form-text">{{ ui_t('pages.physical.shelf_help') ?? '' }}</div>
                        </div>

                        <div class="col-md-6">
                            <label for="boxInput" class="form-label">{{ ui_t('pages.physical.fields.box') }}</label>
                            <input
                                type="number"
                                id="boxInput"
                                name="box"
                                class="form-control"
                                placeholder="{{ ui_t('pages.physical.box_placeholder') ?? '' }}"
                                min="0"
                                value="{{ old('box', $location->box) }}"
                                required
                                aria-describedby="boxHelp"
                            >
                            <div id="boxHelp" class="form-text">{{ ui_t('pages.physical.box_help') ?? '' }}</div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ ui_t('actions.cancel') }}</button>
                    <button type="submit" class="btn btn-success">{{ ui_t('actions.update') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
