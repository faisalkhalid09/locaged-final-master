<div class="stats-grid">
    <a href="{{ route('documents.all', ['show_expired' => 1, 'page_title' => 'all_documents']) }}" class="text-decoration-none text-reset">
    <div class="stat-card red">
        <div class="stat-icon">
            <i class="fa-solid fa-file"></i>
        </div>
        <div class="stat-content">
            <div class="d-flex justify-content-between">
                <h3>{{ $totalDocuments }}</h3>
                {{--<span class="stat-change"
                >+11.01% <i class="fa-solid fa-arrow-trend-up"></i
                    ></span>--}}
            </div>
            <p>{{ ui_t('pages.stats.all_documents') }}</p>
        </div>
    </div>
    </a>

    @if(auth()->user() && auth()->user()->hasRole('user'))
        {{-- Service Users: show pending documents on All Documents page --}}
        <a href="{{ route('documents.all', ['status' => \App\Enums\DocumentStatus::Pending->value, 'page_title' => 'pending_documents', 'show_expired' => 1]) }}" class="text-decoration-none text-reset">
    @else
        {{-- Other roles: show pending approvals page --}}
        <a href="{{ route('documents.status', ['show_expired' => 1]) }}" class="text-decoration-none text-reset">
    @endif
    <div class="stat-card blue">
        <div class="stat-icon">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-content">
            <div class="d-flex justify-content-between">
                <h3>{{ $statusSummary['pending'] }}</h3>
                {{-- <span class="stat-change"
                 >-0.01% <i class="fa-solid fa-arrow-trend-down"></i
                     ></span>--}}
            </div>
            <p>{{ ui_t('pages.stats.pending') }}</p>
        </div>
    </div>
    </a>


    <a href="{{ route('documents.all', ['status' => \App\Enums\DocumentStatus::Approved->value, 'page_title' => 'approved_documents', 'show_expired' => 1, 'hide_status_filter' => 1]) }}" class="text-decoration-none text-reset">
    <div class="stat-card green">
        <div class="stat-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-content">
            <div class="d-flex justify-content-between">
                <h3>{{ $statusSummary['approved'] }}</h3>
                {{--   <span class="stat-change"
                   >-0.01% <i class="fa-solid fa-arrow-trend-down"></i
                       ></span>--}}
            </div>
            <p>{{ ui_t('pages.stats.approved') }}</p>
        </div>
    </div>
    </a>


    <a href="{{ route('documents.all', ['status' => \App\Enums\DocumentStatus::Declined->value, 'page_title' => 'declined_documents', 'show_expired' => 1, 'hide_status_filter' => 1]) }}" class="text-decoration-none text-reset">
    <div class="stat-card yellow">
        <div class="stat-icon">
            <i class="fa-solid fa-user-group"></i>
        </div>
        <div class="stat-content">
            <div class="d-flex justify-content-between">
                <h3>{{ $statusSummary['declined'] }}</h3>
                {{--  <span class="stat-change"
                  >+11.01% <i class="fa-solid fa-arrow-trend-up"></i
                      ></span>--}}
            </div>
            <p>{{ ui_t('pages.stats.declined') }}</p>
        </div>
    </div>
    </a>


</div>
