<div class="card border-0 categories-section p-4 h-100">
    <div class="mb-3">
        <p class="text-muted mb-1 fw-semibold">{{ ui_t('pages.rooms_cards.rooms') }}</p>
        <h5 class="fw-bold">{{ ui_t('pages.rooms_cards.storage_by_room') }}</h5>
    </div>

    @php
        $roomColors = [
            ['bg' => '#fff7da', 'bar' => '#f0d672'], // Yellow
            ['bg' => '#ffe5e7', 'bar' => '#e63946'], // Red
            ['bg' => '#e5f7f0', 'bar' => '#47a778'], // Green
            ['bg' => '#e5f0ff', 'bar' => '#68a0fd'], // Blue
        ];
    @endphp

    <div class="row g-3">
        @foreach($roomCards as $index => $room)
            @php $color = $roomColors[$index % count($roomColors)]; @endphp
            <div class="col-6 col-md-4 col-lg-3">
                @php
                    $url = isset($room['type']) && $room['type'] === 'box'
                        ? route('documents.all') . '?box_id=' . $room['id']
                        : route('documents.all') . '?room=' . ($room['room'] ?? $room['name']);
                @endphp
                <a href="{{ $url }}" class="text-decoration-none">
                    <div class="border rounded-3 p-3 h-100 position-relative" style="background-color: {{ $color['bg'] }};">
                        <div class="position-absolute top-0 start-0 end-0" style="height: 4px; border-radius: 12px 12px 0 0; background-color: {{ $color['bar'] }};"></div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="fs-4">{{ isset($room['type']) && $room['type'] === 'box' ? '📦' : '📍' }}</span>
                            <div class="fw-semibold text-dark text-truncate" title="{{ $room['name'] ?? $room['room'] }}">
                                {{ $room['name'] ?? $room['room'] }}
                            </div>
                        </div>
                        @if(isset($room['full_path']))
                            <small class="text-muted d-block mb-2 text-truncate" style="font-size: 0.75rem;" title="{{ $room['full_path'] }}">
                                {{ \Illuminate\Support\Str::limit($room['full_path'], 30) }}
                            </small>
                        @endif
                        <div class="text-muted small">{{ $room['count'] }} {{ ui_t('pages.rooms_cards.docs') }}</div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
</div>

