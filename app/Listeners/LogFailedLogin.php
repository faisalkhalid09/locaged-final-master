<?php

namespace App\Listeners;

use App\Models\AuthenticationLog;
use Illuminate\Auth\Events\Failed;
use Illuminate\Support\Facades\Request;

class LogFailedLogin
{
    public function handle(Failed $event): void
    {
        AuthenticationLog::create([
            'user_id' => null, // Failed login - user might not exist
            'email' => $event->credentials['email'] ?? 'unknown',
            'type' => 'login_failed',
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'occurred_at' => now(),
        ]);
    }
}
