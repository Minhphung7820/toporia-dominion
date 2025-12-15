<?php

declare(strict_types=1);

namespace Toporia\Dominion\Cache;

use Toporia\Framework\Cache\Contracts\CacheInterface;
use Toporia\Framework\Support\Collection\Collection;
use Toporia\Dominion\Models\Role;
use Toporia\Dominion\Models\Permission;

/**
 * Class PermissionCache
 *
 * Handles caching of permissions for optimal performance.
 * Provides O(1) permission checks after initial cache load.
 *
 * @author      Phungtruong7820 <minhphung485@gmail.com>
 * @copyright   Copyright (c) 2025 Toporia Framework
 * @license     MIT
 */
final class PermissionCache
{
    /**
     * Cache interface.
     *
     * @var CacheInterface
     */
    private CacheInterface $cache;

    /**
     * Cache key generator.
     *
     * @var CacheKeyGenerator
     */
    private CacheKeyGenerator $keyGenerator;

    /**
     * Cache TTL in seconds.
     *
     * @var int
     */
    private int $ttl;

    /**
     * Whether caching is enabled.
     *
     * @var bool
     */
    private bool $enabled;

    /**
     * Cache tags for selective clearing.
     *
     * @var array
     */
    private array $tags;

    /**
     * Create a new permission cache instance.
     *
     * @param CacheInterface $cache
     * @param CacheKeyGenerator|null $keyGenerator
     */
    public function __construct(CacheInterface $cache, ?CacheKeyGenerator $keyGenerator = null)
    {
        $this->cache = $cache;
        $this->keyGenerator = $keyGenerator ?? new CacheKeyGenerator();
        $this->ttl = (int) config('dominion.cache.ttl', 86400);
        $this->enabled = (bool) config('dominion.cache.enabled', true);
        $this->tags = config('dominion.cache.tags', ['rbac']);
    }

    /**
     * Get cached permissions for a user.
     *
     * @param int|string $userId
     * @param callable $loader Function to load permissions from database
     * @return array Permission names
     */
    public function getPermissions(int|string $userId, callable $loader): array
    {
        if (!$this->enabled) {
            return $loader();
        }

        $key = $this->keyGenerator->forUserPermissions($userId);

        return $this->remember($key, $loader);
    }

    /**
     * Get cached roles for a user.
     *
     * @param int|string $userId
     * @param callable $loader Function to load roles from database
     * @return array Role names
     */
    public function getRoles(int|string $userId, callable $loader): array
    {
        if (!$this->enabled) {
            return $loader();
        }

        $key = $this->keyGenerator->forUserRoles($userId);

        return $this->remember($key, $loader);
    }

    /**
     * Get cached permissions for a role.
     *
     * @param int|string $roleId
     * @param callable $loader Function to load permissions from database
     * @return array Permission names
     */
    public function getRolePermissions(int|string $roleId, callable $loader): array
    {
        if (!$this->enabled) {
            return $loader();
        }

        $key = $this->keyGenerator->forRolePermissions($roleId);

        return $this->remember($key, $loader);
    }

    /**
     * Get cached role by name.
     *
     * @param string $name
     * @param callable $loader
     * @return Role|null
     */
    public function getRole(string $name, callable $loader): ?Role
    {
        if (!$this->enabled) {
            return $loader();
        }

        $key = $this->keyGenerator->forRoleByName($name);

        return $this->remember($key, $loader);
    }

    /**
     * Get cached permission by name.
     *
     * @param string $name
     * @param callable $loader
     * @return Permission|null
     */
    public function getPermission(string $name, callable $loader): ?Permission
    {
        if (!$this->enabled) {
            return $loader();
        }

        $key = $this->keyGenerator->forPermissionByName($name);

        return $this->remember($key, $loader);
    }

    /**
     * Get all cached roles.
     *
     * @param callable $loader
     * @return Collection
     */
    public function getAllRoles(callable $loader): Collection
    {
        if (!$this->enabled) {
            return $loader();
        }

        $key = $this->keyGenerator->forAllRoles();

        return $this->remember($key, $loader);
    }

    /**
     * Get all cached permissions.
     *
     * @param callable $loader
     * @return Collection
     */
    public function getAllPermissions(callable $loader): Collection
    {
        if (!$this->enabled) {
            return $loader();
        }

        $key = $this->keyGenerator->forAllPermissions();

        return $this->remember($key, $loader);
    }

    /**
     * Forget cache for a specific user.
     *
     * @param int|string $userId
     * @return void
     */
    public function forgetUser(int|string $userId): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->cache->forget($this->keyGenerator->forUserPermissions($userId));
        $this->cache->forget($this->keyGenerator->forUserRoles($userId));
    }

    /**
     * Forget cache for a specific role.
     *
     * @param int|string $roleId
     * @return void
     */
    public function forgetRole(int|string $roleId): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->cache->forget($this->keyGenerator->forRolePermissions($roleId));
    }

    /**
     * Forget cache for a role by name.
     *
     * @param string $name
     * @return void
     */
    public function forgetRoleByName(string $name): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->cache->forget($this->keyGenerator->forRoleByName($name));
        $this->cache->forget($this->keyGenerator->forAllRoles());
    }

    /**
     * Forget cache for a permission by name.
     *
     * @param string $name
     * @return void
     */
    public function forgetPermissionByName(string $name): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->cache->forget($this->keyGenerator->forPermissionByName($name));
        $this->cache->forget($this->keyGenerator->forAllPermissions());
    }

    /**
     * Flush all RBAC cache.
     *
     * @return void
     */
    public function flush(): void
    {
        if (!$this->enabled) {
            return;
        }

        // Try to use tags if cache driver supports it
        if (method_exists($this->cache, 'tags') && !empty($this->tags)) {
            try {
                $this->cache->tags($this->tags)->flush();
                return;
            } catch (\Throwable $e) {
                // Fallback to manual clearing
            }
        }

        // Manual clearing of known keys
        $this->cache->forget($this->keyGenerator->forAllRoles());
        $this->cache->forget($this->keyGenerator->forAllPermissions());

        // Note: For a complete flush, the application should track
        // user/role IDs or use a cache driver that supports tags/patterns
    }

    /**
     * Check if caching is enabled.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Enable caching.
     *
     * @return void
     */
    public function enable(): void
    {
        $this->enabled = true;
    }

    /**
     * Disable caching.
     *
     * @return void
     */
    public function disable(): void
    {
        $this->enabled = false;
    }

    /**
     * Set the cache TTL.
     *
     * @param int $ttl
     * @return void
     */
    public function setTtl(int $ttl): void
    {
        $this->ttl = $ttl;
    }

    /**
     * Get the cache TTL.
     *
     * @return int
     */
    public function getTtl(): int
    {
        return $this->ttl;
    }

    /**
     * Remember a value in cache.
     *
     * @param string $key
     * @param callable $loader
     * @return mixed
     */
    private function remember(string $key, callable $loader): mixed
    {
        $cached = $this->cache->get($key);

        if ($cached !== null) {
            return $cached;
        }

        $value = $loader();

        $this->cache->put($key, $value, $this->ttl);

        return $value;
    }
}
