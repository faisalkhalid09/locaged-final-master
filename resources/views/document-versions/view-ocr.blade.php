@extends('layouts.app')

@section('content')
    <div class=" mt-5">

        <!-- Row 1 -->
        <div class="doc-row d-flex align-items-center">
            <div class="col-1 d-flex justify-content-center">
                @php
                    $ext = strtolower(pathinfo($doc->file_path, PATHINFO_EXTENSION));
                    $iconClass = getFileIcon($ext);
                @endphp
                <div class="file-icon">
                    <i class="{{ $iconClass }}" style="font-size: 36px;"></i>
                    <span class="visually-hidden">{{ ui_t('pages.documents.filetype.' . $fileType) }}</span>
                </div>
            </div>
            <div class="col-3">
                <div class="doc-title">{{ $doc->document->title }}</div>
                <div class="doc-meta">{{ ui_t('pages.ocr_view.document_name') }}</div>
            </div>
            <div class="col-2">
                <div class="doc-title">{{ $doc->document->metadata['author'] }}</div>
                <div class="doc-meta">{{ ui_t('pages.ocr_view.author') }}</div>
            </div>
            <div class="col-2">
                <div class="doc-title">{{ optional($doc->document->created_at)->format('Y-m-d')  }}</div>
                <div class="doc-meta">{{ ui_t('pages.ocr_view.creation_date') }}</div>
            </div>
            <div class="col-2">
                <div class="doc-title">{{ optional($doc->document->expire_at)->format('Y-m-d') }}</div>
                <div class="doc-meta">{{ ui_t('pages.ocr_view.expire_date') }}</div>
            </div>
            <div class="col-2">
                <span class="badge-status ">{{ ui_t('pages.documents.status.pending') }}</span>
                <div class="doc-meta">{{ ui_t('pages.ocr_view.status') }}</div>
            </div>
        </div>
        <div class="row mb-5 mt-5 ">
            <div class="col-md-6 mb-5 mb-md-0 ">
                <div class="form-box bord" style="height: 85vh">
                    @if($fileType === 'image')
                        <img src="{{ $fileUrl }}" class="w-100" alt="{{ ui_t('pages.versions.image_preview') }}" />
                    @elseif($fileType === 'pdf')
                        <iframe src="{{ $fileUrl }}" width="100%" height="100%" style="border:none;"></iframe>
                    @elseif(in_array($fileType, ['doc', 'excel']) && $pdfUrl)
                        {{-- Show converted PDF if available (same as fullscreen view) --}}
                        <iframe src="{{ $pdfUrl }}" width="100%" height="100%" style="border:none;"></iframe>
                    @elseif($fileType === 'excel')
                        <div id="office-preview-container-ocr"
                             class="h-100 border rounded bg-white"
                             data-file-url="{{ $fileUrl }}"
                             data-file-type="excel"
                             style="overflow: auto;">
                            <div class="d-flex h-100 justify-content-center align-items-center text-muted small">
                                {{ ui_t('pages.versions.loading_preview') ?? 'Loading Excel preview…' }}
                            </div>
                        </div>
                    @elseif($fileType === 'doc')
                        <div id="office-preview-container-ocr"
                             class="h-100 border rounded bg-white"
                             data-file-url="{{ $fileUrl }}"
                             data-file-type="doc"
                             style="overflow: auto;">
                            <div class="d-flex h-100 justify-content-center align-items-center text-muted small">
                                {{ ui_t('pages.versions.loading_preview') ?? 'Loading Word preview…' }}
                            </div>
                        </div>
                    @elseif($fileType === 'video')
                        <video controls class="w-100" style="max-height: 100%;">
                            <source src="{{ $fileUrl }}" type="video/mp4">
                            <source src="{{ $fileUrl }}" type="video/avi">
                            <source src="{{ $fileUrl }}" type="video/mov">
                        {{ ui_t('pages.ocr_view.video_not_supported') }}
                        </video>
                    @elseif($fileType === 'audio')
                        <div class="d-flex align-items-center justify-content-center h-100">
                            <div class="text-center">
                                <i class="fas fa-file-audio fa-5x text-info mb-3"></i>
                                <h5>{{ ui_t('pages.ocr_view.audio_file') }}</h5>
                                <p class="text-muted">{{ $doc->document->title }}</p>
                                <audio controls class="w-100 mb-3">
                                    <source src="{{ $fileUrl }}" type="audio/mpeg">
                                    <source src="{{ $fileUrl }}" type="audio/wav">
                                    <source src="{{ $fileUrl }}" type="audio/ogg">
                                {{ ui_t('pages.ocr_view.audio_not_supported') }}
                                </audio>
                                <br>
                                <a href="{{ $fileUrl }}" class="btn btn-info" download>
                                    <i class="fas fa-download"></i> {{ ui_t('pages.ocr_view.download_file') }}
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="d-flex align-items-center justify-content-center h-100">
                            <div class="text-center">
                                <i class="fas fa-file fa-5x text-secondary mb-3"></i>
                                <h5>{{ ui_t('pages.ocr_view.not_available') }}</h5>
                                <p class="text-muted">{{ $doc->document->title }}</p>
                                <a href="{{ $fileUrl }}" class="btn btn-secondary" download>
                                    <i class="fas fa-download"></i> {{ ui_t('pages.ocr_view.download_file') }}
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            <div class="col-md-6 ">
                <div class="form-box shadow-sm bg-white bord">
                    <form method="post" action="{{ route('document-versions.update.ocr',['id' => $doc->id]) }}">
                        @csrf
                        @method('PUT')
                        <div class="mt-4 p-4">
                            <div class=" mb-3">
                                <label for="fileName" class="form-label">{{ ui_t('pages.ocr_view.extracted_text') }}</label>
                            </div>

                            <textarea
                                class="form-control"
                                placeholder="{{ ui_t('pages.ocr_view.no_text_yet') }}"
                                rows="10"
                                style="min-height: 200px; resize: vertical;"
                                name="ocr_text"
                            >{{ $doc->ocr_text }}
                            </textarea>

                            <div class="mt-4 d-flex justify-content-end">
                                <button type="button" class="btn-upload me-2" id="download-ocr-text">
                                    <i class="fa-solid fa-download"></i> {{ ui_t('pages.ocr_view.download') }}
                                </button>
                                @can('update',$doc->document)
                                    @canany(['view any ocr job', 'view department ocr job', 'view own ocr job'])
                                        <button type="submit" class="btn-upload "><i class="fa-solid fa-check"></i> {{ ui_t('pages.ocr_view.submit') }}</button>
                                    @endcanany
                                @endcan

                            </div>
                        </div>




                    </form>
                </div>
            </div>
        </div>

    </div>
    <script>
        document.getElementById('download-ocr-text').addEventListener('click', function () {
            const text = document.querySelector('textarea[name="ocr_text"]').value;
            const blob = new Blob([text], { type: 'text/plain' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'ocr.txt';
            link.click();
            URL.revokeObjectURL(link.href);
        });
    </script>

    {{-- External libs for client-side Office previews (shared with main preview) --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.4.12/mammoth.browser.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <script>
        // Client-side Office preview for OCR view (reuses same libs as main preview)
        (function () {
            const container = document.getElementById('office-preview-container-ocr');
            if (!container) return;

            const fileUrl = container.dataset.fileUrl;
            const fileType = container.dataset.fileType;

            function showError(msg) {
                container.innerHTML = '<div class="d-flex h-100 justify-content-center align-items-center text-danger small">' + msg + '</div>';
            }

            if (fileType === 'excel') {
                if (typeof window.XLSX === 'undefined') {
                    showError('Excel preview library not loaded.');
                    return;
                }
                fetch(fileUrl, { credentials: 'same-origin' })
                    .then(r => r.arrayBuffer())
                    .then(buf => {
                        const wb = XLSX.read(buf, { type: 'array' });
                        const sheetName = wb.SheetNames[0];
                        const sheet = wb.Sheets[sheetName];
                        const html = XLSX.utils.sheet_to_html(sheet, { editable: false });
                        container.innerHTML = html;
                        container.style.overflow = 'auto';
                    })
                    .catch(() => showError('Failed to load Excel preview.'));
            } else if (fileType === 'doc') {
                if (typeof window.mammoth === 'undefined') {
                    showError('Word preview library not loaded.');
                    return;
                }
                fetch(fileUrl, { credentials: 'same-origin' })
                    .then(r => r.arrayBuffer())
                    .then(buf => window.mammoth.convertToHtml({ arrayBuffer: buf }))
                    .then(result => {
                        container.innerHTML = '<div class="word-html-preview">' + result.value + '</div>';
                        container.style.overflowX = 'auto';
                        container.style.overflowY = 'auto';
                    })
                    .catch(() => showError('Failed to load Word preview.'));
            }
        })();
    </script>

    <style>
        .word-html-preview {
            min-width: 900px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px 40px;
            box-sizing: border-box;
        }
        .word-html-preview p {
            margin-bottom: 0.5rem;
        }
    </style>
@endsection
