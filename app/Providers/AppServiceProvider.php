<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\RolePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Set application timezone from Branding settings
        $timezone = \App\Support\Branding::getTimezone();
        if ($timezone && $timezone !== 'UTC') {
            config(['app.timezone' => $timezone]);
            date_default_timezone_set($timezone);
        }

        // AUDIT FIX #2: Runtime guard - block app if debug is enabled in production
        // This prevents accidental exposure of stack traces and sensitive information
        if ($this->app->environment('production') && config('app.debug') === true) {
            abort(503, 'Application misconfigured: APP_DEBUG must be false in production.');
        }

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(\App\Models\DocumentDestructionRequest::class, \App\Policies\DocumentDestructionRequestPolicy::class);

        Gate::before(function (User $user, string $ability) {
            
            // Only master role gets special access to everything
            if ($user->hasRole('master')) {
                return true;
            }
            
            // super_admin is treated like normal users - no special access
            // if ($user->hasRole('super_admin')) {
            //     return true;
            // }
            
            return null;
        });

    }
}
