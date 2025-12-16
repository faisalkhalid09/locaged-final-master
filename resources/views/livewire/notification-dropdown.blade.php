<div class="position-relative" x-data="{ open: false }" @keydown.escape.window="open = false">
    <button type="button" class="btn p-0 border-0 bg-transparent position-relative" @click="open = !open" :aria-expanded="open ? 'true' : 'false'" aria-haspopup="true" aria-label="{{ ui_t('pages.notifications.title') }}">
        <i class="fas fa-bell fs-5"></i>
        @if($notifications->count() > 0)
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">{{ $notifications->count() }}</span>
        @endif
    </button>

    <div
        class="position-absolute end-0 mt-2 z-3"
        x-show="open"
        x-cloak
        x-transition.opacity
        @click.outside="open = false"
        role="menu"
        aria-label="{{ ui_t('pages.notifications.notification_panel') }}"
    >
        <div class="card shadow border-0 notif-panel">
            <div class="card-header bg-white d-flex align-items-center justify-content-between py-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-bell"></i>
                    <span class="fw-semibold">{{ ui_t('pages.notifications.title') }}</span>
                    @if($notifications->count() > 0)
                        <span class="badge bg-primary">{{ $notifications->count() }}</span>
                    @endif
                </div>
                @if($notifications->isNotEmpty())
                    <button wire:click="markAllAsRead" class="btn btn-link btn-sm text-decoration-none">{{ ui_t('pages.notifications.mark_all_as_read') }}</button>
                @endif
            </div>

            <div class="list-group list-group-flush" style="max-height: 360px; overflow: auto;">
                @if($notifications->isEmpty())
                    <div class="list-group-item text-center text-muted py-4">{{ ui_t('pages.messages.no_unread_notifications') }}</div>
                @else
                    @foreach($notifications as $notification)
                        @include('components.notification-item',[ 'notification' => $notification ])
                    @endforeach
                @endif
            </div>

            <div class="card-footer bg-white d-flex justify-content-end">
                <a href="{{ route('notifications') }}" class="btn btn-sm btn-outline-primary">{{ ui_t('pages.notifications.view_all') }}</a>
            </div>
        </div>
    </div>

</div>


<script>
    document.addEventListener('DOMContentLoaded', function () {
        window.addEventListener('notify', event => {
            console.log(event)
            Toastify({
                text: event.detail[0].title + ": " + event.detail[0].body,
                duration: 5000,
                close: true,
                gravity: "top", // top or bottom
                position: "right", // left, center or right
                backgroundColor: "linear-gradient(to right, #00b09b, #96c93d)",
            }).showToast();
        });

    });
</script>

