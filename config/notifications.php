<?php

return [
    'types' => [
        'approved' => [
            'type' => 'success',
            'title' => 'Document approved',
            'body' => 'The document ":title" has been approved.',
            'icon' => 'assets/approved.png',
        ],
'created' => [
        'type' => 'success',
        'title' => 'Document created',
        'body' => 'The document ":title" has been created.',
        'icon' => 'assets/created.png',
    ],
    'pending_approval_1w' => [
        'type' => 'pending_approval_1w',
        'title' => 'Approval Reminder', // Key: Approval Reminder
        'body' => 'This document has been pending approval for a week.', // Key: This document...
        'icon' => 'heroicon-o-clock',
    ],

    'pending_approval_1m' => [
        'type' => 'pending_approval_1m',
        'title' => 'Approval Reminder', // Key: Approval Reminder
        'body' => 'This document has been pending approval for a month.', // Key: This document...
        'icon' => 'heroicon-o-exclamation-triangle',
    ],
    'unlocked' => [
            'type' => 'success',
            'title' => 'Document unlocked',
            'body' => 'The document ":title" has been unlocked.',
            'icon' => 'assets/created.png',
        ],
        'expired' => [
            'type' => 'warning',
            'title' => 'Document expired',
            'body' => 'The document ":title" has expired.',
            'icon' => 'assets/expired.png',
        ],
        'locked' => [
            'type' => 'danger',
            'title' => 'Document locked',
            'body' => 'The document ":title" has been locked.',
            'icon' => 'assets/declined.png',
        ],
        'declined' => [
            'type' => 'danger',
            'title' => 'Document declined',
            'body' => 'The document ":title" has been declined.',
            'icon' => 'assets/declined.png',
        ],
        'archived' => [
            'type' => 'danger',
            'title' => 'Document archived',
            'body' => 'The document ":title" has been archived.',
            'icon' => 'assets/declined.png',
        ],
'destroyed' => [
            'type' => 'danger',
            'title' => 'Document destroyed',
            'body' => 'The document ":title" has been destroyed.',
            'icon' => 'assets/declined.png',
        ],
        'permanently_deleted' => [
            'type' => 'danger',
            'title' => 'Document permanently deleted',
            'body' => 'The document ":title" has been permanently deleted from the system.',
            'icon' => 'assets/declined.png',
        ],
        'renamed' => [
            'type' => 'info',
            'title' => 'Document renamed',
            'body' => 'The document ":title" has been renamed.',
            'icon' => 'assets/created.png',
        ],
        'moved' => [
            'type' => 'info',
            'title' => 'Document moved',
            'body' => 'The document ":title" has been moved.',
            'icon' => 'assets/created.png',
        ],
    ],
];
