<!-- Preview Modal -->
<div wire:ignore.self class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel"
     aria-hidden="true" data-unsupported-text="{{ ui_t('pages.upload.file_type_not_supported') }}">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewModalLabel">{{ ui_t('pages.upload.file_preview') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ ui_t('actions.close') }}"></button>
            </div>
            <div class="modal-body preview-modal-body" id="previewContent">
                @if($previewUrl)
                    @php
                        $isImage = false;
                        $isPdf = false;
                        try {
                            $mime = $previewMime ?? '';
                            $name = strtolower($previewName ?? '');
                            $isImage = str_starts_with($mime, 'image/');
                            $isPdf = $mime === 'application/pdf' || str_ends_with($name, '.pdf');
                        } catch (\Throwable $e) {
                            $ext = strtolower(pathinfo(parse_url($previewUrl, PHP_URL_PATH), PATHINFO_EXTENSION));
                            $isImage = in_array($ext, ['png','jpg','jpeg','gif','bmp','svg','webp']);
                            $isPdf = $ext === 'pdf';
                        }
                    @endphp

                    @if($isImage)
                        <img src="{{ $previewUrl }}" alt="{{ ui_t('pages.upload.image_preview') }}" class="img-fluid" />
                    @else
                        {{-- For PDFs, Office docs, text, and any other type, let the browser handle it in an iframe. --}}
                        <div class="h-100">
                            <iframe src="{{ $previewUrl }}" title="{{ $isPdf ? ui_t('pages.upload.pdf_preview') : ui_t('pages.upload.file_preview') }}" class="w-100 h-100 border-0 bg-white"></iframe>
                        </div>
                        <p class="mt-2 small text-muted">
                            {{ ui_t('pages.upload.file_preview_fallback') }}
                            <a href="{{ $previewUrl }}" target="_blank" rel="noopener">{{ ui_t('pages.upload.open_in_new_tab') }}</a>.
                        </p>
                    @endif
                @else
                    <p class="text-muted">{{ ui_t('pages.upload.no_preview') }}</p>
                @endif
            </div>
        </div>
    </div>
</div>
