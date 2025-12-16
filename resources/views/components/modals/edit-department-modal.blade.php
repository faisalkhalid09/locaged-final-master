@props([
    'department'
])
<div class="modal fade" id="editModal{{ $department->id }}" tabindex="-1" aria-labelledby="editModalLabel{{ $department->id }}" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" action="{{ route('departments.update', $department->id) }}">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel{{ $department->id }}">{{ ui_t('pages.structures_page.edit') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ ui_t('actions.close') }}"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>{{ ui_t('tables.name') }}</label>
                        <input type="text" name="name" class="form-control" value="{{ $department->name }}" required>
                    </div>
                    <div class="mb-3">
                        <label>{{ ui_t('tables.description') }}</label>
                        <textarea name="description" class="form-control" rows="3">{{ $department->description }}</textarea>
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
