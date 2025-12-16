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
     * Get only the active statuses for filters (excluding commented out ones)
     */
    public static function activeCases(): array
    {
        return [
            self::Pending,
            self::Declined,
            self::Approved,
            self::Archived,
            self::Destroyed,
        ];
    }
}
