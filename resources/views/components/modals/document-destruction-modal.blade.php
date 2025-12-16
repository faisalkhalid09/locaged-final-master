<!-- Request Destruction Modal -->
<div class="modal fade" id="destroyDocumentModal-{{ $doc->id }}" tabindex="-1" aria-labelledby="destroyDocumentLabel-{{ $doc->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('documents-destructions.store', $doc->id) }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="destroyDocumentLabel-{{ $doc->id }}">{{ ui_t('pages.documents.request_destruction_title') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ ui_t('actions.close') }}"></button>
                </div>

                <div class="modal-body">
                    <p>{{ ui_t('pages.documents.request_destruction_confirm') }}</p>

                    <!-- Hidden document_id -->
                    <input type="hidden" name="document_id" value="{{ $doc->id }}">

                    <!-- No implementation/location required anymore -->
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-danger">{{ ui_t('pages.documents.request_destruction_button') }}</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ ui_t('pages.documents.cancel') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
