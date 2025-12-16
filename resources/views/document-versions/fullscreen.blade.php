@extends('layouts.app')

@section('content')
    <style>
        .preview-container {
            background: #f8f9fa;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            height: calc(100vh - 120px);
            display: flex;
            flex-direction: column;
        }
        .preview-header {
            background: #fff;
            border-bottom: 1px solid #dee2e6;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .preview-content {
            flex: 1;
            width: 100%;
            height: 100%;
            overflow: auto;
            background: #fff;
        }
        .preview-content iframe,
        .preview-content img {
            width: 100%;
            height: 100%;
            border: none;
        }
        .preview-content video {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .btn-preview {
            background: #fff;
            border: 1px solid #dee2e6;
            color: #495057;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }
        .btn-preview:hover {
            background: #f8f9fa;
            border-color: #adb5bd;
            color: #212529;
        }
    </style>

    <div class="container-fluid mt-3">
        <div class="preview-container">
            <div class="preview-header">
                {{-- Empty spacer for left side --}}
                <div style="width: 120px;"></div>

                {{-- Center: Navigation and Title --}}
                <div class="d-flex align-items-center gap-3">
                    {{-- Previous Document Button --}}
                    @if($prevDocUrl)
                        <a href="{{ $prevDocUrl }}" 
                           class="btn-preview" 
                           title="{{ $prevDocTitle ?? 'Previous Document' }}">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    @else
                        <button class="btn-preview" disabled title="No previous document">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                    @endif

                    {{-- Document Title --}}
                    <div class="text-center">
                        <strong>{{ $doc->document->title }}</strong>
                        <span class="ms-3 text-muted small">Version {{ $doc->version_number }}</span>
                    </div>

                    {{-- Next Document Button --}}
                    @if($nextDocUrl)
                        <a href="{{ $nextDocUrl }}" 
                           class="btn-preview" 
                           title="{{ $nextDocTitle ?? 'Next Document' }}">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    @else
                        <button class="btn-preview" disabled title="No next document">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    @endif
                </div>

                {{-- Right: Action buttons --}}
                <div class="d-flex gap-2">
                    <a href="{{ route('documents.download', ['id' => $doc->document->id]) }}" 
                       class="btn-preview" 
                       title="{{ ui_t('actions.download') ?? 'Download' }}">
                        <i class="fas fa-download"></i>
                    </a>
                    <button onclick="window.history.back()" 
                            class="btn-preview" 
                            title="{{ ui_t('actions.close') ?? 'Close' }}">
                        <i class="fas fa-times"></i> {{ ui_t('actions.close') ?? 'Close' }}
                    </button>
                </div>
            </div>

            <div class="preview-content">
                @if($fileType === 'pdf')
                    <iframe src="{{ $fileUrl }}" type="application/pdf"></iframe>
                @elseif($fileType === 'image')
                    <img src="{{ $fileUrl }}" alt="{{ $doc->document->title }}" style="object-fit: contain;" />
                @elseif(in_array($fileType, ['doc', 'excel']) && $pdfUrl)
                    {{-- Show converted PDF if available --}}
                    <iframe src="{{ $pdfUrl }}" type="application/pdf"></iframe>
                @elseif($fileType === 'video')
                    <video controls autoplay>
                        <source src="{{ $fileUrl }}" type="video/mp4">
                        <source src="{{ $fileUrl }}" type="video/avi">
                        <source src="{{ $fileUrl }}" type="video/mov">
                        {{ ui_t('pages.versions.video_not_supported') ?? 'Your browser does not support the video tag.' }}
                    </video>
                @elseif($fileType === 'audio')
                    <div class="d-flex align-items-center justify-content-center h-100">
                        <div class="text-center">
                            <i class="fas fa-file-audio fa-5x mb-3 text-primary"></i>
                            <h5>{{ $doc->document->title }}</h5>
                            <audio controls autoplay class="mt-3" style="width: 500px; max-width: 90%;">
                                <source src="{{ $fileUrl }}" type="audio/mpeg">
                                <source src="{{ $fileUrl }}" type="audio/wav">
                                <source src="{{ $fileUrl }}" type="audio/ogg">
                                {{ ui_t('pages.versions.audio_not_supported') ?? 'Your browser does not support the audio element.' }}
                            </audio>
                        </div>
                    </div>
                @else
                    {{-- Fallback for unsupported file types --}}
                    <div class="d-flex align-items-center justify-content-center h-100">
                        <div class="text-center">
                            <i class="fas fa-file fa-5x mb-3 text-muted"></i>
                            <h5>{{ ui_t('pages.versions.preview_not_available') ?? 'Preview not available' }}</h5>
                            <p class="text-muted">{{ $doc->document->title }}</p>
                            <a href="{{ route('documents.download', ['id' => $doc->document->id]) }}" 
                               class="btn btn-primary mt-3">
                                <i class="fas fa-download"></i> {{ ui_t('actions.download') ?? 'Download' }}
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        // Close on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                window.history.back();
            }
        });
    </script>
@endsection
