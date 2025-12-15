<?php

declare(strict_types=1);

namespace Toporia\Dominion\Contracts;

use Toporia\Framework\Support\Collection\Collection;

/**
 * Interface DominionManagerInterface
 *
 * Contract for the main RBAC manager.
 *
 * @author      Phungtruong7820 <minhphung485@gmail.com>
 * @copyright   Copyright (c) 2025 Toporia Framework
 * @license     MIT
 */
interface DominionManagerInterface
{
    /**
     * Find a role by name.
     *
     * @param string $name
     * @return RoleInterface|null
     */
    public function findRole(string $name): ?RoleInterface;

    /**
     * Find a permission by name.
     *
     * @param string $name
     * @return PermissionInterface|null
     */
    public function findPermission(string $name): ?PermissionInterface;

    /**
     * Get all roles.
     *
     * @return Collection
     */
    public function getAllRoles(): Collection;

    /**
     * Get all permissions.
     *
     * @return Collection
     */
    public function getAllPermissions(): Collection;

    /**
     * Create a new role.
     *
     * @param string $name
     * @param string|null $displayName
     * @param string|null $description
     * @return RoleInterface
     */
    public function createRole(string $name, ?string $displayName = null, ?string $description = null): RoleInterface;

    /**
     * Create a new permission.
     *
     * @param string $name
     * @param string|null $displayName
     * @param string|null $description
     * @return PermissionInterface
     */
    public function createPermission(string $name, ?string $displayName = null, ?string $description = null): PermissionInterface;

    /**
     * Delete a role by name.
     *
     * @param string $name
     * @return bool
     */
    public function deleteRole(string $name): bool;

    /**
     * Delete a permission by name.
     *
     * @param string $name
     * @return bool
     */
    public function deletePermission(string $name): bool;

    /**
     * Get permissions by resource name.
     *
     * @param string $resource
     * @return Collection
     */
    public function getPermissionsByResource(string $resource): Collection;

    /**
     * Check if a user is a super admin.
     *
     * @param mixed $user
     * @return bool
     */
    public function isSuperAdmin(mixed $user): bool;

    /**
     * Get cached permissions for a user.
     *
     * @param int|string $userId
     * @return array
     */
    public function getCachedPermissions(int|string $userId): array;

    /**
     * Clear permission cache for a user.
     *
     * @param int|string|null $userId Null to clear all
     * @return void
     */
    public function clearCache(int|string|null $userId = null): void;

    /**
     * Get the role model class.
     *
     * @return string
     */
    public function getRoleClass(): string;

    /**
     * Get the permission model class.
     *
     * @return string
     */
    public function getPermissionClass(): string;
}
