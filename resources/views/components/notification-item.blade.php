<div class="list-group-item py-2" wire:key="{{ $notification->id }}">
    <div class="d-flex align-items-start  gap-2">
        <img
            src="{{ (is_array($notification->data ?? null) && array_key_exists('icon', $notification->data)) ? asset($notification->data['icon']) : asset('assets/created.png') }}"
            alt="{{ ui_t('pages.notifications.notification_icon') }}"
            class="rounded flex-shrink-0 avatar-40"
        />

        <div class="flex-grow-1 min-w-0">
            <div class="d-flex align-items-start justify-content-between">
                <div class="fw-semibold text-break">
                    {{ $notification->data['title'] ?? ui_t('pages.notifications.notification') }}
                </div>
                <small class="text-muted ms-2 flex-shrink-0">{{ $notification->created_at->diffForHumans() }}</small>
            </div>
            <div class="text-muted small mt-1 line-clamp-2">
                {{ $notification->data['body'] ?? '' }}
            </div>
        </div>

        <div class="ms-2">  
            <button wire:click="deleteNotification('{{ $notification->id }}')" class="btn btn-sm btn-link text-danger p-0" title="{{ ui_t('actions.delete') }}" aria-label="{{ ui_t('actions.delete') }} {{ ui_t('pages.notifications.notification') }}">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    </div>
</div>
