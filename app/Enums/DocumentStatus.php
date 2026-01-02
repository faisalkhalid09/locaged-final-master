<?php

namespace App\Enums;

enum DocumentStatus: string
{
    case Pending   = 'pending';
    case Declined  = 'declined';
    case Approved  = 'approved';
    // case Locked    = 'locked';     // Commented out - may need later
    // case Unlocked  = 'unlocked';   // Commented out - may need later
    // case Moved     = 'moved';      // Commented out - may need later
    case Archived  = 'archived';
    case Destroyed = 'destroyed';

    /**
     * Get only the active statuses for filters (excluding archived/destroyed)
     */
    public static function activeCases(): array
    {
        return [
            self::Pending,
            self::Declined,
            self::Approved,
            // Note: 'expired' is handled separately via is_expired flag, not this enum
        ];
    }

    /**
     * Virtual status for expired documents filter
     */
    public const EXPIRED = 'expired';
}
