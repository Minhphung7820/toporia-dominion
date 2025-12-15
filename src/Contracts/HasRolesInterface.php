<?php

declare(strict_types=1);

namespace Toporia\Dominion\Contracts;

use DateTimeInterface;
use Toporia\Framework\Support\Collection\Collection;

/**
 * Interface HasRolesInterface
 *
 * Contract for models that can have roles assigned.
 *
 * @author      Phungtruong7820 <minhphung485@gmail.com>
 * @copyright   Copyright (c) 2025 Toporia Framework
 * @license     MIT
 */
interface HasRolesInterface
{
    /**
     * Get all roles assigned to this model.
     *
     * @return Collection
     */
    public function getRoles(): Collection;

    /**
     * Get all role names.
     *
     * @return Collection
     */
    public function getRoleNames(): Collection;

    /**
     * Assign role(s) to this model.
     *
     * @param string|array|RoleInterface ...$roles
     * @return static
     */
    public function assignRole(string|array|RoleInterface ...$roles): static;

    /**
     * Remove role(s) from this model.
     *
     * @param string|array|RoleInterface ...$roles
     * @return static
     */
    public function removeRole(string|array|RoleInterface ...$roles): static;

    /**
     * Sync roles (replace all existing roles).
     *
     * @param array $roles
     * @return static
     */
    public function syncRoles(array $roles): static;

    /**
     * Check if model has a specific role.
     *
     * @param string|array|RoleInterface $roles
     * @return bool
     */
    public function hasRole(string|array|RoleInterface $roles): bool;

    /**
     * Check if model has all of the given roles.
     *
     * @param array $roles
     * @return bool
     */
    public function hasAllRoles(array $roles): bool;

    /**
     * Check if model has any of the given roles.
     *
     * @param array $roles
     * @return bool
     */
    public function hasAnyRole(array $roles): bool;

    /**
     * Check if model has exactly the given roles.
     *
     * @param array $roles
     * @return bool
     */
    public function hasExactRoles(array $roles): bool;

    /**
     * Get all permissions (from roles and direct assignments).
     *
     * @return Collection
     */
    public function getAllPermissions(): Collection;

    /**
     * Get all permission names.
     *
     * @return Collection
     */
    public function getPermissionNames(): Collection;

    /**
     * Check if model has a specific permission.
     *
     * @param string|array|PermissionInterface $permission
     * @return bool
     */
    public function hasPermissionTo(string|array|PermissionInterface $permission): bool;

    /**
     * Check if model has all of the given permissions.
     *
     * @param array $permissions
     * @return bool
     */
    public function hasAllPermissions(array $permissions): bool;

    /**
     * Check if model has any of the given permissions.
     *
     * @param array $permissions
     * @return bool
     */
    public function hasAnyPermission(array $permissions): bool;

    /**
     * Give direct permission(s) to this model.
     *
     * @param string|array|PermissionInterface ...$permissions
     * @return static
     */
    public function givePermissionTo(string|array|PermissionInterface ...$permissions): static;

    /**
     * Revoke direct permission(s) from this model.
     *
     * @param string|array|PermissionInterface ...$permissions
     * @return static
     */
    public function revokePermissionTo(string|array|PermissionInterface ...$permissions): static;

    /**
     * Sync direct permissions (replace all existing direct permissions).
     *
     * @param array $permissions
     * @return static
     */
    public function syncPermissions(array $permissions): static;

    /**
     * Check if model has a direct permission (not via role).
     *
     * @param string|PermissionInterface $permission
     * @return bool
     */
    public function hasDirectPermission(string|PermissionInterface $permission): bool;

    /**
     * Check if model has permission via a role.
     *
     * @param string|PermissionInterface $permission
     * @return bool
     */
    public function hasPermissionViaRole(string|PermissionInterface $permission): bool;
}
