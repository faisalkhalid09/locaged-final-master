@props([
    'rule'
])
<div class="modal fade" id="editModal{{ $rule->id }}" tabindex="-1" aria-labelledby="editModalLabel{{ $rule->id }}" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" action="{{ route('workflow-rules.update', $rule->id) }}">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel{{ $rule->id }}">{{ ui_t('pages.workflow.edit') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ ui_t('actions.close') }}"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>{{ ui_t('pages.workflow.from_status') }}</label>
                        <select name="from_status" class="form-control" required>
                            <option value="" disabled selected>{{ ui_t('pages.workflow.from_status') }}</option>
                            @foreach (\App\Enums\DocumentStatus::cases() as $status)
                                <option value="{{ $status->value }}" {{ old('from_status',$rule->from_status) == $status->value ? 'selected' : '' }}>
                                    {{ ui_t('pages.documents.status.' . $status->value) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>{{ ui_t('pages.workflow.to_status') }}</label>
                        <select name="to_status" class="form-control" required>
                            <option value="" disabled selected>{{ ui_t('pages.workflow.to_status') }}</option>
                            @foreach (\App\Enums\DocumentStatus::cases() as $status)
                                <option value="{{ $status->value }}" {{ old('to_status',$rule->to_status) == $status->value ? 'selected' : '' }}>
                                    {{ ui_t('pages.documents.status.' . $status->value) }}
                                </option>
                            @endforeach
                        </select>
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
