<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class Branding
{
    protected static function storagePath(): string
    {
        return storage_path('app/branding.json');
    }

    protected static function read(): array
    {
        $path = self::storagePath();
        if (! file_exists($path)) {
            return [];
        }
        $json = file_get_contents($path);
        $data = json_decode($json, true);
        return is_array($data) ? $data : [];
    }

    protected static function write(array $data): void
    {
        $path = self::storagePath();
        @file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    public static function set(string $key, string $relativePublicPath): void
    {
        $data = self::read();
        $data[$key] = $relativePublicPath; // e.g., branding/your-file.jpg (disk=public)
        self::write($data);
    }

    public static function get(string $key, ?string $defaultRelativeAsset = null): ?string
    {
        $data = self::read();
        $val = $data[$key] ?? null;
        if ($val) {
            // Stored as path relative to public disk, build full URL
            return asset('storage/' . ltrim($val, '/'));
        }
        if ($defaultRelativeAsset) {
            return asset($defaultRelativeAsset);
        }
        return null;
    }

    public static function headerLogoUrl(): string
    {
        return self::get('header_logo', 'assets/clogo.jpg');
    }

    public static function loginImageUrl(): string
    {
        return self::get('login_left_image', 'assets/cbanner.jpg');
    }

    /**
     * Get the maximum number of users allowed in the system.
     * Returns 0 if no limit is set (unlimited users).
     */
    public static function getMaxUsers(): int
    {
        $data = self::read();
        return (int) ($data['max_users'] ?? 0);
    }

    /**
     * Set the maximum number of users allowed in the system.
     * Set to 0 for unlimited users.
     */
    public static function setMaxUsers(int $maxUsers): void
    {
        $data = self::read();
        $data['max_users'] = $maxUsers;
        self::write($data);
    }

    /**
     * Check if the user limit has been reached.
     * Returns true if the current user count equals or exceeds the maximum allowed.
     */
    public static function isUserLimitReached(): bool
    {
        $maxUsers = self::getMaxUsers();
        if ($maxUsers <= 0) {
            return false; // No limit set
        }
        
        $currentUserCount = \App\Models\User::count();
        return $currentUserCount >= $maxUsers;
    }

    /**
     * Get the number of remaining user slots available.
     * Returns -1 if unlimited users are allowed.
     * Returns 0 or positive number if there's a limit.
     */
    public static function getRemainingUserSlots(): int
    {
        $maxUsers = self::getMaxUsers();
        if ($maxUsers <= 0) {
            return -1; // Unlimited
        }
        
        $currentUserCount = \App\Models\User::count();
        return max(0, $maxUsers - $currentUserCount);
    }

    /**
     * Get the application timezone.
     * Returns UTC if no timezone is set.
     */
    public static function getTimezone(): string
    {
        $data = self::read();
        return $data['timezone'] ?? 'UTC';
    }

    /**
     * Set the application timezone.
     */
    public static function setTimezone(string $timezone): void
    {
        $data = self::read();
        $data['timezone'] = $timezone;
        self::write($data);
    }
}


