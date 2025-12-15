<?php

declare(strict_types=1);

namespace Toporia\Dominion\Cache;

/**
 * Class CacheKeyGenerator
 *
 * Generates consistent cache keys for RBAC caching.
 *
 * @author      Phungtruong7820 <minhphung485@gmail.com>
 * @copyright   Copyright (c) 2025 Toporia Framework
 * @license     MIT
 */
final class CacheKeyGenerator
{
    /**
     * Cache key prefix.
     *
     * @var string
     */
    private string $prefix;

    /**
     * Create a new cache key generator.
     *
     * @param string|null $prefix
     */
    public function __construct(?string $prefix = null)
    {
        $this->prefix = $prefix ?? config('dominion.cache.prefix', 'rbac_');
    }

    /**
     * Generate cache key for user permissions.
     *
     * @param int|string $userId
     * @return string
     */
    public function forUserPermissions(int|string $userId): string
    {
        return $this->prefix . 'user_permissions_' . $userId;
    }

    /**
     * Generate cache key for user roles.
     *
     * @param int|string $userId
     * @return string
     */
    public function forUserRoles(int|string $userId): string
    {
        return $this->prefix . 'user_roles_' . $userId;
    }

    /**
     * Generate cache key for role permissions.
     *
     * @param int|string $roleId
     * @return string
     */
    public function forRolePermissions(int|string $roleId): string
    {
        return $this->prefix . 'role_permissions_' . $roleId;
    }

    /**
     * Generate cache key for all roles.
     *
     * @return string
     */
    public function forAllRoles(): string
    {
        return $this->prefix . 'all_roles';
    }

    /**
     * Generate cache key for all permissions.
     *
     * @return string
     */
    public function forAllPermissions(): string
    {
        return $this->prefix . 'all_permissions';
    }

    /**
     * Generate cache key for role by name.
     *
     * @param string $name
     * @return string
     */
    public function forRoleByName(string $name): string
    {
        return $this->prefix . 'role_name_' . md5($name);
    }

    /**
     * Generate cache key for permission by name.
     *
     * @param string $name
     * @return string
     */
    public function forPermissionByName(string $name): string
    {
        return $this->prefix . 'permission_name_' . md5($name);
    }

    /**
     * Generate cache key for permissions by resource.
     *
     * @param string $resource
     * @return string
     */
    public function forPermissionsByResource(string $resource): string
    {
        return $this->prefix . 'permissions_resource_' . md5($resource);
    }

    /**
     * Get the cache prefix.
     *
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * Get pattern for clearing user-related cache.
     *
     * @return string
     */
    public function getUserPattern(): string
    {
        return $this->prefix . 'user_*';
    }

    /**
     * Get pattern for clearing role-related cache.
     *
     * @return string
     */
    public function getRolePattern(): string
    {
        return $this->prefix . 'role_*';
    }

    /**
     * Get pattern for clearing permission-related cache.
     *
     * @return string
     */
    public function getPermissionPattern(): string
    {
        return $this->prefix . 'permission_*';
    }
}
