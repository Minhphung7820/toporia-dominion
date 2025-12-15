<?php

declare(strict_types=1);

namespace Toporia\Dominion;

use Toporia\Framework\Support\Collection\Collection;
use Toporia\Dominion\Cache\PermissionCache;
use Toporia\Dominion\Models\Role;
use Toporia\Dominion\Models\Permission;

/**
 * Class DominionRegistrar
 *
 * Handles registration and caching of roles and permissions.
 * Provides efficient lookups with O(1) performance after cache load.
 *
 * @author      Phungtruong7820 <minhphung485@gmail.com>
 * @copyright   Copyright (c) 2025 Toporia Framework
 * @license     MIT
 */
final class DominionRegistrar
{
    /**
     * Permission cache instance.
     *
     * @var PermissionCache
     */
    private PermissionCache $cache;

    /**
     * Role model class.
     *
     * @var string
     */
    private string $roleClass;

    /**
     * Permission model class.
     *
     * @var string
     */
    private string $permissionClass;

    /**
     * In-memory role cache for O(1) lookups.
     *
     * @var array<string, Role>|null
     */
    private ?array $rolesCache = null;

    /**
     * In-memory permission cache for O(1) lookups.
     *
     * @var array<string, Permission>|null
     */
    private ?array $permissionsCache = null;

    /**
     * Create a new registrar instance.
     *
     * @param PermissionCache $cache
     */
    public function __construct(PermissionCache $cache)
    {
        $this->cache = $cache;
        $this->roleClass = config('dominion.models.role', Role::class);
        $this->permissionClass = config('dominion.models.permission', Permission::class);
    }

    /**
     * Get all roles.
     *
     * @return Collection
     */
    public function getRoles(): Collection
    {
        return $this->cache->getAllRoles(function () {
            return $this->roleClass::active()->get();
        });
    }

    /**
     * Get all permissions.
     *
     * @return Collection
     */
    public function getPermissions(): Collection
    {
        return $this->cache->getAllPermissions(function () {
            return $this->permissionClass::active()->get();
        });
    }

    /**
     * Find a role by name.
     *
     * @param string $name
     * @return Role|null
     */
    public function findRole(string $name): ?Role
    {
        // Check in-memory cache first (O(1))
        if ($this->rolesCache !== null && isset($this->rolesCache[$name])) {
            return $this->rolesCache[$name];
        }

        // Load from persistent cache or database
        $role = $this->cache->getRole($name, function () use ($name) {
            return $this->roleClass::findByName($name);
        });

        // Store in in-memory cache
        if ($role !== null) {
            $this->rolesCache[$name] = $role;
        }

        return $role;
    }

    /**
     * Find a permission by name.
     *
     * @param string $name
     * @return Permission|null
     */
    public function findPermission(string $name): ?Permission
    {
        // Check in-memory cache first (O(1))
        if ($this->permissionsCache !== null && isset($this->permissionsCache[$name])) {
            return $this->permissionsCache[$name];
        }

        // Load from persistent cache or database
        $permission = $this->cache->getPermission($name, function () use ($name) {
            return $this->permissionClass::findByName($name);
        });

        // Store in in-memory cache
        if ($permission !== null) {
            $this->permissionsCache[$name] = $permission;
        }

        return $permission;
    }

    /**
     * Get permissions for a user.
     *
     * @param int|string $userId
     * @param callable $loader
     * @return array
     */
    public function getPermissionsForUser(int|string $userId, callable $loader): array
    {
        return $this->cache->getPermissions($userId, $loader);
    }

    /**
     * Get roles for a user.
     *
     * @param int|string $userId
     * @param callable $loader
     * @return array
     */
    public function getRolesForUser(int|string $userId, callable $loader): array
    {
        return $this->cache->getRoles($userId, $loader);
    }

    /**
     * Get permissions by resource.
     *
     * @param string $resource
     * @return Collection
     */
    public function getPermissionsByResource(string $resource): Collection
    {
        return $this->getPermissions()->filter(
            fn($permission) => $permission->resource === $resource
        );
    }

    /**
     * Build in-memory cache for O(1) lookups.
     *
     * @return void
     */
    public function buildCache(): void
    {
        $this->rolesCache = [];
        $this->permissionsCache = [];

        foreach ($this->getRoles() as $role) {
            $this->rolesCache[$role->name] = $role;
        }

        foreach ($this->getPermissions() as $permission) {
            $this->permissionsCache[$permission->name] = $permission;
        }
    }

    /**
     * Clear all caches.
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->rolesCache = null;
        $this->permissionsCache = null;
        $this->cache->flush();
    }

    /**
     * Clear cache for a specific user.
     *
     * @param int|string $userId
     * @return void
     */
    public function clearUserCache(int|string $userId): void
    {
        $this->cache->forgetUser($userId);
    }

    /**
     * Get the cache instance.
     *
     * @return PermissionCache
     */
    public function getCache(): PermissionCache
    {
        return $this->cache;
    }

    /**
     * Get the role model class.
     *
     * @return string
     */
    public function getRoleClass(): string
    {
        return $this->roleClass;
    }

    /**
     * Get the permission model class.
     *
     * @return string
     */
    public function getPermissionClass(): string
    {
        return $this->permissionClass;
    }
}
