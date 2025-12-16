<?php

namespace App\Enums;

enum DocumentDestructionStatus: string
{
    case Pending   = 'pending';
    case Accepted  = 'accepted';
    case Rejected  = 'rejected';
    case Postponed = 'postponed';
    case Implemented   = 'implemented';
}
