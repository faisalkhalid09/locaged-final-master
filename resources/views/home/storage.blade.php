@extends('layouts.app')

@section('content')
    <div class="mt-3 position-relative mb-5">
        <div class="overview-section px-3 px-md-0 mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <h2>{{ ui_t('pages.storage.title') }}</h2>
            </div>
            <p class="text-muted mb-0">
                {{ ui_t('pages.storage.description') }}
            </p>
        </div>

        @php
            $usedPercent   = $disk['used_percent'] ?? null;
            $warning       = $thresholds['warning'] ?? 80;
            $critical      = $thresholds['critical'] ?? 90;
            $statusMessage = null;
            $statusClass   = 'text-success';

            if (! is_null($usedPercent)) {
                if ($usedPercent >= $critical) {
                    $statusMessage = ui_t('pages.storage.status_critical');
                    $statusClass   = 'text-danger';
                } elseif ($usedPercent >= $warning) {
                    $statusMessage = ui_t('pages.storage.status_warning');
                    $statusClass   = 'text-warning';
                } else {
                    $statusMessage = ui_t('pages.storage.status_normal');
                    $statusClass   = 'text-success';
                }
            }

            $progressClass = 'bg-success';
            if (! is_null($usedPercent)) {
                if ($usedPercent >= $critical) {
                    $progressClass = 'bg-danger';
                } elseif ($usedPercent >= $warning) {
                    $progressClass = 'bg-warning';
                }
            }
        @endphp

        <div class="row g-4">
            <div class="col-lg-7 col-md-12">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title mb-3">{{ ui_t('pages.storage.server_disk_usage') }}</h5>

                        @if(is_null($disk['total_bytes']))
                            <p class="text-danger mb-0">{{ ui_t('pages.storage.unable_to_read') }}</p>
                        @else
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span>{{ ui_t('pages.storage.total_disk_space') }}</span>
                                    <strong>{{ $disk['total_human'] }}</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span>{{ ui_t('pages.storage.used') }}</span>
                                    <strong>{{ $disk['used_human'] ?? 'N/A' }} @if(!is_null($usedPercent)) ({{ $usedPercent }}%) @endif</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>{{ ui_t('pages.storage.free') }}</span>
                                    <strong>{{ $disk['free_human'] ?? 'N/A' }} @if(!is_null($disk['free_percent'])) ({{ $disk['free_percent'] }}%) @endif</strong>
                                </div>

                                @if(!is_null($usedPercent))
                                    <div class="progress" style="height: 18px;">
                                        <div class="progress-bar {{ $progressClass }}" role="progressbar"
                                             style="width: {{ min(100, max(0, $usedPercent)) }}%;"
                                             aria-valuenow="{{ $usedPercent }}" aria-valuemin="0" aria-valuemax="100">
                                            {{ $usedPercent }}%
                                        </div>
                                    </div>
                                @endif
                            </div>

                            @if($statusMessage)
                                <p class="mt-2 mb-0 {{ $statusClass }}">{{ $statusMessage }}</p>
                            @endif
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-5 col-md-12">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title mb-3">{{ ui_t('pages.storage.app_documents_usage') }}</h5>

                        <p class="mb-2">{{ ui_t('pages.storage.app_documents_description') }}</p>

                        <div class="d-flex justify-content-between mb-1">
                            <span>{{ ui_t('pages.storage.size_used_by_documents') }}</span>
                            <strong>{{ $appStorage['human'] }}</strong>
                        </div>

                        <div class="d-flex justify-content-between mb-1">
                            <span>{{ ui_t('pages.storage.share_of_total_disk') }}</span>
                            <strong>
                                @if(!is_null($appStorage['percent_of_disk']))
                                    {{ $appStorage['percent_of_disk'] }}%
                                @else
                                    N/A
                                @endif
                            </strong>
                        </div>

                        <div class="d-flex justify-content-between mb-1">
                            <span>{{ ui_t('pages.storage.share_of_used_space') }}</span>
                            <strong>
                                @if(!is_null($appStorage['percent_of_used']))
                                    {{ $appStorage['percent_of_used'] }}%
                                @else
                                    N/A
                                @endif
                            </strong>
                        </div>

                        <p class="text-muted small mt-3 mb-0">
                            {{ ui_t('pages.storage.values_calculated') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
