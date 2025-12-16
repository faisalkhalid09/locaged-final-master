<div class="card border-0 categories-section p-4 h-100">
    <div class="mb-3">
        <p class="text-muted mb-1 fw-semibold" id="donutSubtitle">{{ $donutChartData['subtitle'] }}</p>
        <h5 class="fw-bold" id="donutTitle">{{ $donutChartData['title'] }}</h5>
    </div>
    <div style="height: 320px">
        <canvas id="docTypesDonut" 
                data-stats='@json($documentTypeStats)' 
                data-donut-data='@json($donutChartData)'
                data-chart-type="{{ $donutChartData['type'] }}"
                data-no-data-label="{{ ui_t('pages.chart.no_data') }}"></canvas>
    </div>
    <div id="donutBackButton" class="mt-2" style="display: none;">
        <button class="btn btn-sm btn-outline-secondary" onclick="goBackToDepartments()">
            <i class="fas fa-arrow-left me-1"></i> {{ ui_t('pages.back_to_departments') }}
        </button>
    </div>
</div>

