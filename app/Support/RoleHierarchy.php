<?php

namespace App\Support;

use App\Models\User;
use Spatie\Permission\Models\Role;

class RoleHierarchy
{
    /**
     * Define role ranks (higher number = higher privilege)
     *
     * Note: keys are lowercased role names as stored by Spatie (spaces preserved).
     */
    public const ROLE_RANK = [
        'master' => 999,                 // platform owner
        'super administrator' => 100,    // general direction
        'super_admin' => 100,            // legacy alias, if ever used
        'admin' => 90,                   // legacy admin role

        // New canonical role names
        'admin de pole' => 80,
        'admin de departments' => 75,
        'admin de cellule' => 70,
        'user' => 60,                    // service-level user

        // Backward-compatibility aliases for older role names
        'department administrator' => 80,
        'division chief' => 75,
        'service manager' => 70,
        'service user' => 60,
    ];

    public static function getRoleRank(string $roleName): int
    {
        // Unknown roles default to 20 (slightly above basic user but well below admins)
        return self::ROLE_RANK[strtolower($roleName)] ?? 20;
    }

    public static function getUserMaxRank(User $user): int
    {
        $roles = $user->roles->pluck('name')->all();
        if (empty($roles)) {
            return 0;
        }
        return max(array_map([self::class, 'getRoleRank'], $roles));
    }

/**
     * Roles current user may VIEW.
     * - Master: may view all roles.
     * - Others: may only view users whose max role rank is STRICTLY lower
     *   than their own (no equal-rank or higher users).
     */
    public static function allowedRoleNamesFor(User $currentUser): array
    {
        // Master can view all roles
        if ($currentUser->hasRole('master')) {
            return Role::pluck('name')->all();
        }

        $currentRank = self::getUserMaxRank($currentUser);
        $allRoles = Role::pluck('name')->all();
        return array_values(array_filter($allRoles, function ($roleName) use ($currentRank) {
            return self::getRoleRank($roleName) < $currentRank;
        }));
    }

    /**
     * Roles current user may ASSIGN to others.
     * - Master: any role (including master).
     * - Others: only roles with STRICTLY lower rank than their own.
     */
    public static function allowedAssignableRoleNamesFor(User $currentUser): array
    {
        if ($currentUser->hasRole('master')) {
            return Role::pluck('name')->all();
        }

        $currentRank = self::getUserMaxRank($currentUser);
        $allRoles = Role::pluck('name')->all();
        return array_values(array_filter($allRoles, function ($roleName) use ($currentRank) {
            return self::getRoleRank($roleName) < $currentRank;
        }));
    }

    public static function canAssignRole(User $currentUser, Role $targetRole): bool
    {
        // Master can assign any role, including master itself
        if ($currentUser->hasRole('master')) {
            return true;
        }

        $currentRank = self::getUserMaxRank($currentUser);
        $targetRank = self::getRoleRank($targetRole->name);

        // Non-master users may only assign roles STRICTLY below their own rank
        return $targetRank < $currentRank;
    }

    public static function canViewUser(User $currentUser, User $other): bool
    {
        return self::getUserMaxRank($other) <= self::getUserMaxRank($currentUser);
    }
}


