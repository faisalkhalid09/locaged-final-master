@extends('layouts.app')

@section('content')

    <div class="pt-2 position-relative">
        <div class="d-md-flex mt-5">
            <h4 class="mb-4">{{ ui_t('nav.ocr') }}</h4>
        </div>
        <div class="form-section">

           {{-- <div class="upload-section p-3 mt-2">




                <label class="form-label fw-semibold text-muted">
                    <img src="{{ asset('assets/Vector (25).svg') }}" alt=""> {{ ui_t('pages.upload.upload_file') }}
                </label>

                <div class="upload-box border border-dashed rounded-3 text-center p-5">
                    <div class="mb-3">
                        <img src="{{ asset('assets/Vector (24).svg') }}" alt="upload">
                    </div>
                    <p class="text-drag">{{ ui_t('pages.upload.drag_drop') }}</p>
                    <button type="button" class="btn btn-dark mt-2">
                        <i class="fa-solid fa-folder-open me-2"></i> {{ ui_t('pages.upload.browse_file') }}
                    </button>
                </div>

            </div>--}}

        </div>
        <div class="activity-log px-4 px-md-0 my-4 ">
            <h6>{{ ui_t('tables.recent_jobs') }}</h6>
            <table class="files-table">
                <thead>
                <tr>
                    <th>{{ ui_t('tables.file_name') }}</th>
                    <th>{{ ui_t('tables.file_created_date') }}</th>
                    <th class="d-flex justify-content-center">{{ ui_t('tables.status') }}</th>
                    <th class="text-center">{{ ui_t('tables.results') }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach($ocrJobs as $job)
                    <tr>
                        <td>
                            <div class="file-item">
                                <div class="file-icon">
                                    @if($job->documentVersion->file_path)
                                        @php
                                            $extension = strtolower(pathinfo($job->documentVersion->file_path, PATHINFO_EXTENSION));
                                            $iconClass = getFileIcon($extension);
                                        @endphp
                                        <i class="{{ $iconClass }}" style="font-size: 24px;"></i>
                                    @else
                                        <i class="fas fa-file text-secondary" style="font-size: 24px;"></i>
                                    @endif
                                </div>
                                <div>
                                    <div class="file-name">{{ $job->documentVersion->document?->title }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="file-date">{{ $job->documentVersion->created_at }}</div>
                        </td>
                        <td class="d-flex justify-content-center">
                            <button class="status-badge pending border-0">
                                {{ ui_t('pages.ocr_jobs.status.' . $job->status) ?? ucfirst($job->status) }}
                            </button>
                        </td>
                        <td class="text-center">
                            @can('view', $job->documentVersion->document)
                                @canany(['view any ocr job', 'view department ocr job', 'view own ocr job'])
                                    <a href="{{ route('document-versions.ocr', ['id' => $job->documentVersion->id]) }}" class="btn-action text-decoration-none">
                                        {{ ui_t('tables.view_results') }}
                                    </a>
                                @endcanany
                            @endcan
                        </td>

                    </tr>
                @endforeach


                </tbody>
            </table>

            <x-pagination :items="$ocrJobs" />
        </div>
    </div>


@endsection
