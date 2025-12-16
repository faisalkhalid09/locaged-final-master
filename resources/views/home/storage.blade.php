@extends('layouts.app')

@section('content')
    <div class="mt-3 position-relative mb-5">
        <div class="overview-section px-3 px-md-0 mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <h2>Storage &amp; Server Space</h2>
            </div>
            <p class="text-muted mb-0">
                Overview of disk usage on the server and space used by application documents. This page is only
                available to Master and Super Administrator roles.
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
                    $statusMessage = 'Critical: disk is almost full. Consider adding more storage as soon as possible.';
                    $statusClass   = 'text-danger';
                } elseif ($usedPercent >= $warning) {
                    $statusMessage = 'Warning: disk usage is high. Plan to add more storage soon.';
                    $statusClass   = 'text-warning';
                } else {
                    $statusMessage = 'Disk usage is within normal limits.';
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
                        <h5 class="card-title mb-3">Server Disk Usage</h5>

                        @if(is_null($disk['total_bytes']))
                            <p class="text-danger mb-0">Unable to read disk statistics on this server.</p>
                        @else
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Total disk space</span>
                                    <strong>{{ $disk['total_human'] }}</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Used</span>
                                    <strong>{{ $disk['used_human'] ?? 'N/A' }} @if(!is_null($usedPercent)) ({{ $usedPercent }}%) @endif</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Free</span>
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
                        <h5 class="card-title mb-3">Application Documents Usage</h5>

                        <p class="mb-2">Documents stored by the application (local storage disk).</p>

                        <div class="d-flex justify-content-between mb-1">
                            <span>Size used by documents</span>
                            <strong>{{ $appStorage['human'] }}</strong>
                        </div>

                        <div class="d-flex justify-content-between mb-1">
                            <span>Share of total disk</span>
                            <strong>
                                @if(!is_null($appStorage['percent_of_disk']))
                                    {{ $appStorage['percent_of_disk'] }}%
                                @else
                                    N/A
                                @endif
                            </strong>
                        </div>

                        <div class="d-flex justify-content-between mb-1">
                            <span>Share of used space</span>
                            <strong>
                                @if(!is_null($appStorage['percent_of_used']))
                                    {{ $appStorage['percent_of_used'] }}%
                                @else
                                    N/A
                                @endif
                            </strong>
                        </div>

                        <p class="text-muted small mt-3 mb-0">
                            Values are calculated based on files stored on the application's local storage disk.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
