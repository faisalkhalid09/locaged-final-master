<div class="card border-0 categories-section p-4 h-100">
    <div
        class="d-flex  align-items-start flex-wrap mb-5"
    >
        <div>
            <p class="text-muted mb-1 fw-semibold">{{ ui_t('pages.chart.statistics') }}</p>
            <h5 class="fw-bold">{{ ui_t('pages.chart.summary_approvals') }}</h5>
        </div>
        <div class="btn-group mb-2 ms-3" id="chartFilters">
            <button type="button" class="btn  me-4 button-timeframe" data-period="weekly">{{ ui_t('pages.chart.weekly') }}</button>
            <button type="button" class="btn  me-4 button-timeframe button-active" data-period="monthly">{{ ui_t('pages.chart.monthly') }}</button>
            <button type="button" class="btn button-timeframe" data-period="yearly">{{ ui_t('pages.chart.yearly') }}</button>
        </div>
    </div>

    <div class="row h-100">
        <div class="col-md-10" style="overflow-x: visible;">
            <div style="height: 100%; overflow-x: visible;">
                <canvas
                    id="approvalChart"
                    data-weekly='@json($weeklyData)'
                    data-monthly='@json($monthlyData)'
                    data-yearly='@json($yearlyData)'
                    data-approved-label="{{ ui_t('pages.chart.approved') }}"
                    data-pending-label="{{ ui_t('pages.chart.pending') }}"
                    data-declined-label="{{ ui_t('pages.chart.rejected') }}"
                    data-expired-label="{{ ui_t('pages.chart.expired') }}"
                ></canvas>

            </div>
        </div>
        <div class="col-md-2 d-flex align-items-center ">
            <div class="border-chart ps-2 d-flex align-items-center">
                <div class="">
                    <div
                        class="border border-black rounded-pill px-3 py-2 d-inline-block mb-2"
                    >
                        <i
                            class="bi bi-circle-fill text-secondary me-1"
                            style="font-size: 0.6rem"
                        ></i>
                        <span
                            class="bg-black rounded-circle d-inline-block"
                            style="width: 12px; height: 12px"
                        ></span>
                        {{ ui_t('pages.chart.all_status') }}
                    </div>
                    <ul class="list-unstyled mt-3">
                        <li
                            class="d-flex justify-content-between align-items-center mb-2"
                        >
                            <div class="d-flex align-items-center gap-2">
                                <span
                                    class="bg-success rounded-circle d-inline-block"
                                    style="width: 10px; height: 10px"
                                ></span>
                                <span>{{ ui_t('pages.chart.approved') }}</span>
                            </div>
                            <span class="fw-semibold">{{ $statusSummary['approved'] }}</span>
                        </li>
                        <li
                            class="d-flex justify-content-between align-items-center mb-2"
                        >
                            <div class="d-flex align-items-center gap-2">
                                <span
                                    class="bg-warning rounded-circle d-inline-block"
                                    style="width: 10px; height: 10px"
                                ></span>
                                <span>{{ ui_t('pages.chart.pending') }}</span>
                            </div>
                            <span class="fw-semibold">{{ $statusSummary['pending'] }}</span>
                        </li>
                        <li
                            class="d-flex justify-content-between align-items-center"
                        >
                            <div class="d-flex align-items-center gap-2">
                                <span
                                    class="bg-danger rounded-circle d-inline-block"
                                    style="width: 10px; height: 10px"
                                ></span>
                                <span>{{ ui_t('pages.chart.rejected') }}</span>
                            </div>
                            <span class="fw-semibold">{{ $statusSummary['declined'] }}</span>
                        </li>
                        <li class="d-flex justify-content-between align-items-center mt-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="bg-dark rounded-circle d-inline-block" style="width: 10px; height: 10px"></span>
                                <span>{{ ui_t('pages.chart.expired') }}</span>
                            </div>
                            <span class="fw-semibold">{{ $statusSummary['expired'] ?? 0 }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
