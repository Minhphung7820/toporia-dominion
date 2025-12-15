<?php

declare(strict_types=1);

/**
 * Dominion RBAC Helper Functions
 *
 * Global helper functions for convenient RBAC operations.
 *
 * @author      Phungtruong7820 <minhphung485@gmail.com>
 * @copyright   Copyright (c) 2025 Toporia Framework
 * @license     MIT
 */

use Toporia\Dominion\DominionManager;
use Toporia\Dominion\Models\Role;
use Toporia\Dominion\Models\Permission;

if (!function_exists('dominion')) {
    /**
     * Get the Dominion RBAC manager instance.
     *
     * @return DominionManager
     */
    function dominion(): DominionManager
    {
        return app(DominionManager::class);
    }
}

if (!function_exists('rbac')) {
    /**
     * Get the RBAC manager instance (alias for dominion()).
     *
     * @return DominionManager
     */
    function rbac(): DominionManager
    {
        return dominion();
    }
}

if (!function_exists('role')) {
    /**
     * Get a role by name or create a new Role query.
     *
     * @param string|null $name Role name (optional)
     * @return Role|null
     */
    function role(?string $name = null): ?Role
    {
        if ($name === null) {
            return null;
        }

        return dominion()->findRole($name);
    }
}

if (!function_exists('permission')) {
    /**
     * Get a permission by name.
     *
     * @param string|null $name Permission name (optional)
     * @return Permission|null
     */
    function permission(?string $name = null): ?Permission
    {
        if ($name === null) {
            return null;
        }

        return dominion()->findPermission($name);
    }
}

if (!function_exists('has_role')) {
    /**
     * Check if the current authenticated user has a role.
     *
     * @param string|array $roles Role name(s) to check
     * @return bool
     */
    function has_role(string|array $roles): bool
    {
        $user = auth()->user();

        if ($user === null) {
            return false;
        }

        if (!method_exists($user, 'hasRole')) {
            return false;
        }

        return $user->hasRole($roles);
    }
}

if (!function_exists('has_permission')) {
    /**
     * Check if the current authenticated user has a permission.
     *
     * @param string|array $permissions Permission name(s) to check
     * @return bool
     */
    function has_permission(string|array $permissions): bool
    {
        $user = auth()->user();

        if ($user === null) {
            return false;
        }

        if (!method_exists($user, 'hasPermissionTo')) {
            return false;
        }

        return $user->hasPermissionTo($permissions);
    }
}

if (!function_exists('has_any_role')) {
    /**
     * Check if the current authenticated user has any of the given roles.
     *
     * @param array $roles Role names to check
     * @return bool
     */
    function has_any_role(array $roles): bool
    {
        return has_role($roles);
    }
}

if (!function_exists('has_all_roles')) {
    /**
     * Check if the current authenticated user has all of the given roles.
     *
     * @param array $roles Role names to check
     * @return bool
     */
    function has_all_roles(array $roles): bool
    {
        $user = auth()->user();

        if ($user === null) {
            return false;
        }

        if (!method_exists($user, 'hasAllRoles')) {
            return false;
        }

        return $user->hasAllRoles($roles);
    }
}

if (!function_exists('has_any_permission')) {
    /**
     * Check if the current authenticated user has any of the given permissions.
     *
     * @param array $permissions Permission names to check
     * @return bool
     */
    function has_any_permission(array $permissions): bool
    {
        return has_permission($permissions);
    }
}

if (!function_exists('has_all_permissions')) {
    /**
     * Check if the current authenticated user has all of the given permissions.
     *
     * @param array $permissions Permission names to check
     * @return bool
     */
    function has_all_permissions(array $permissions): bool
    {
        $user = auth()->user();

        if ($user === null) {
            return false;
        }

        if (!method_exists($user, 'hasAllPermissions')) {
            return false;
        }

        return $user->hasAllPermissions($permissions);
    }
}

if (!function_exists('is_super_admin')) {
    /**
     * Check if the current authenticated user is a super admin.
     *
     * @return bool
     */
    function is_super_admin(): bool
    {
        $user = auth()->user();

        if ($user === null) {
            return false;
        }

        return dominion()->isSuperAdmin($user);
    }
}
