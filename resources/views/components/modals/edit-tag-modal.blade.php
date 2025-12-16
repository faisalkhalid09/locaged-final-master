@props([
    'tag'
])
<div class="modal fade" id="editModal{{ $tag->id }}" tabindex="-1" aria-labelledby="editModalLabel{{ $tag->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="{{ route('tags.update', $tag->id) }}">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel{{ $tag->id }}">{{ ui_t('pages.tags_page.edit') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ ui_t('actions.close') }}"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>{{ ui_t('pages.tags_page.tag_name') }}</label>
                        <input type="text" name="name" class="form-control" value="{{ $tag->name }}" required>
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
