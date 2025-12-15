<?php

declare(strict_types=1);

namespace Toporia\Dominion;

use Toporia\Framework\Support\Collection\Collection;
use Toporia\Dominion\Cache\PermissionCache;
use Toporia\Dominion\Contracts\DominionManagerInterface;
use Toporia\Dominion\Contracts\RoleInterface;
use Toporia\Dominion\Contracts\PermissionInterface;
use Toporia\Dominion\Exceptions\RoleAlreadyExistsException;
use Toporia\Dominion\Exceptions\PermissionAlreadyExistsException;
use Toporia\Dominion\Exceptions\RoleNotFoundException;
use Toporia\Dominion\Exceptions\PermissionNotFoundException;
use Toporia\Dominion\Models\Role;
use Toporia\Dominion\Models\Permission;

/**
 * Class DominionManager
 *
 * Main RBAC manager providing a fluent API for role and permission management.
 *
 * @author      Phungtruong7820 <minhphung485@gmail.com>
 * @copyright   Copyright (c) 2025 Toporia Framework
 * @license     MIT
 */
final class DominionManager implements DominionManagerInterface
{
    /**
     * RBAC registrar.
     *
     * @var DominionRegistrar
     */
    private DominionRegistrar $registrar;

    /**
     * Configuration array.
     *
     * @var array
     */
    private array $config;

    /**
     * Create a new RBAC manager instance.
     *
     * @param DominionRegistrar $registrar
     * @param array $config
     */
    public function __construct(DominionRegistrar $registrar, array $config = [])
    {
        $this->registrar = $registrar;
        $this->config = $config;
    }

    /**
     * Find a role by name.
     *
     * @param string $name
     * @return RoleInterface|null
     */
    public function findRole(string $name): ?RoleInterface
    {
        return $this->registrar->findRole($name);
    }

    /**
     * Find a role by name or throw exception.
     *
     * @param string $name
     * @return RoleInterface
     * @throws RoleNotFoundException
     */
    public function findRoleOrFail(string $name): RoleInterface
    {
        $role = $this->findRole($name);

        if ($role === null) {
            throw RoleNotFoundException::withName($name);
        }

        return $role;
    }

    /**
     * Find a permission by name.
     *
     * @param string $name
     * @return PermissionInterface|null
     */
    public function findPermission(string $name): ?PermissionInterface
    {
        return $this->registrar->findPermission($name);
    }

    /**
     * Find a permission by name or throw exception.
     *
     * @param string $name
     * @return PermissionInterface
     * @throws PermissionNotFoundException
     */
    public function findPermissionOrFail(string $name): PermissionInterface
    {
        $permission = $this->findPermission($name);

        if ($permission === null) {
            throw PermissionNotFoundException::withName($name);
        }

        return $permission;
    }

    /**
     * Get all roles.
     *
     * @return Collection
     */
    public function getAllRoles(): Collection
    {
        return $this->registrar->getRoles();
    }

    /**
     * Get all permissions.
     *
     * @return Collection
     */
    public function getAllPermissions(): Collection
    {
        return $this->registrar->getPermissions();
    }

    /**
     * Create a new role.
     *
     * @param string $name
     * @param string|null $displayName
     * @param string|null $description
     * @return RoleInterface
     * @throws RoleAlreadyExistsException
     */
    public function createRole(
        string $name,
        ?string $displayName = null,
        ?string $description = null
    ): RoleInterface {
        // Check if role already exists
        if ($this->findRole($name) !== null) {
            throw RoleAlreadyExistsException::withName($name);
        }

        $roleClass = $this->getRoleClass();

        $role = $roleClass::create([
            'name' => $name,
            'display_name' => $displayName,
            'description' => $description,
        ]);

        $this->clearCache();

        return $role;
    }

    /**
     * Create a new permission.
     *
     * @param string $name
     * @param string|null $displayName
     * @param string|null $description
     * @return PermissionInterface
     * @throws PermissionAlreadyExistsException
     */
    public function createPermission(
        string $name,
        ?string $displayName = null,
        ?string $description = null
    ): PermissionInterface {
        // Check if permission already exists
        if ($this->findPermission($name) !== null) {
            throw PermissionAlreadyExistsException::withName($name);
        }

        $permissionClass = $this->getPermissionClass();

        $permission = $permissionClass::create([
            'name' => $name,
            'display_name' => $displayName,
            'description' => $description,
        ]);

        $this->clearCache();

        return $permission;
    }

    /**
     * Find or create a role.
     *
     * @param string $name
     * @param array $attributes
     * @return RoleInterface
     */
    public function findOrCreateRole(string $name, array $attributes = []): RoleInterface
    {
        $role = $this->findRole($name);

        if ($role !== null) {
            return $role;
        }

        $roleClass = $this->getRoleClass();

        return $roleClass::create(array_merge(['name' => $name], $attributes));
    }

    /**
     * Find or create a permission.
     *
     * @param string $name
     * @param array $attributes
     * @return PermissionInterface
     */
    public function findOrCreatePermission(string $name, array $attributes = []): PermissionInterface
    {
        $permission = $this->findPermission($name);

        if ($permission !== null) {
            return $permission;
        }

        $permissionClass = $this->getPermissionClass();

        return $permissionClass::create(array_merge(['name' => $name], $attributes));
    }

    /**
     * Delete a role by name.
     *
     * @param string $name
     * @return bool
     */
    public function deleteRole(string $name): bool
    {
        $role = $this->findRole($name);

        if ($role === null) {
            return false;
        }

        if ($role->isSystem()) {
            throw new \RuntimeException("Cannot delete system role '{$name}'.");
        }

        $role->delete();
        $this->clearCache();

        return true;
    }

    /**
     * Delete a permission by name.
     *
     * @param string $name
     * @return bool
     */
    public function deletePermission(string $name): bool
    {
        $permission = $this->findPermission($name);

        if ($permission === null) {
            return false;
        }

        if ($permission->isSystem()) {
            throw new \RuntimeException("Cannot delete system permission '{$name}'.");
        }

        $permission->delete();
        $this->clearCache();

        return true;
    }

    /**
     * Get permissions by resource name.
     *
     * @param string $resource
     * @return Collection
     */
    public function getPermissionsByResource(string $resource): Collection
    {
        return $this->registrar->getPermissionsByResource($resource);
    }

    /**
     * Create CRUD permissions for a resource.
     *
     * @param string $resource
     * @param array $actions
     * @return Collection
     */
    public function createCrudPermissions(
        string $resource,
        array $actions = ['create', 'read', 'update', 'delete']
    ): Collection {
        $permissionClass = $this->getPermissionClass();

        $permissions = $permissionClass::createCrudPermissions($resource, $actions);
        $this->clearCache();

        return $permissions;
    }

    /**
     * Check if a user is a super admin.
     *
     * @param mixed $user
     * @return bool
     */
    public function isSuperAdmin(mixed $user): bool
    {
        if (!$this->config['super_admin']['enabled'] ?? config('dominion.super_admin.enabled', true)) {
            return false;
        }

        if (!method_exists($user, 'hasRole')) {
            return false;
        }

        $superAdminRole = $this->config['super_admin']['role']
            ?? config('dominion.super_admin.role', 'super-admin');

        return $user->hasRole($superAdminRole);
    }

    /**
     * Get cached permissions for a user.
     *
     * @param int|string $userId
     * @return array
     */
    public function getCachedPermissions(int|string $userId): array
    {
        return $this->registrar->getPermissionsForUser($userId, function () {
            return [];
        });
    }

    /**
     * Clear permission cache.
     *
     * @param int|string|null $userId Null to clear all
     * @return void
     */
    public function clearCache(int|string|null $userId = null): void
    {
        if ($userId !== null) {
            $this->registrar->clearUserCache($userId);
        } else {
            $this->registrar->clearCache();
        }
    }

    /**
     * Get the role model class.
     *
     * @return string
     */
    public function getRoleClass(): string
    {
        return $this->registrar->getRoleClass();
    }

    /**
     * Get the permission model class.
     *
     * @return string
     */
    public function getPermissionClass(): string
    {
        return $this->registrar->getPermissionClass();
    }

    /**
     * Get the registrar instance.
     *
     * @return DominionRegistrar
     */
    public function getRegistrar(): DominionRegistrar
    {
        return $this->registrar;
    }

    /**
     * Build in-memory cache for optimal performance.
     *
     * @return void
     */
    public function buildCache(): void
    {
        $this->registrar->buildCache();
    }

    /**
     * Assign a role to a user.
     *
     * @param mixed $user
     * @param string|RoleInterface $role
     * @return void
     */
    public function assignRole(mixed $user, string|RoleInterface $role): void
    {
        if (!method_exists($user, 'assignRole')) {
            throw new \RuntimeException('User model does not use HasRoles trait.');
        }

        $user->assignRole($role);
    }

    /**
     * Remove a role from a user.
     *
     * @param mixed $user
     * @param string|RoleInterface $role
     * @return void
     */
    public function removeRole(mixed $user, string|RoleInterface $role): void
    {
        if (!method_exists($user, 'removeRole')) {
            throw new \RuntimeException('User model does not use HasRoles trait.');
        }

        $user->removeRole($role);
    }

    /**
     * Give a permission to a user.
     *
     * @param mixed $user
     * @param string|PermissionInterface $permission
     * @return void
     */
    public function givePermission(mixed $user, string|PermissionInterface $permission): void
    {
        if (!method_exists($user, 'givePermissionTo')) {
            throw new \RuntimeException('User model does not use HasRoles trait.');
        }

        $user->givePermissionTo($permission);
    }

    /**
     * Revoke a permission from a user.
     *
     * @param mixed $user
     * @param string|PermissionInterface $permission
     * @return void
     */
    public function revokePermission(mixed $user, string|PermissionInterface $permission): void
    {
        if (!method_exists($user, 'revokePermissionTo')) {
            throw new \RuntimeException('User model does not use HasRoles trait.');
        }

        $user->revokePermissionTo($permission);
    }

    /**
     * Check if user has a role.
     *
     * @param mixed $user
     * @param string|array $roles
     * @return bool
     */
    public function userHasRole(mixed $user, string|array $roles): bool
    {
        if (!method_exists($user, 'hasRole')) {
            return false;
        }

        return $user->hasRole($roles);
    }

    /**
     * Check if user has a permission.
     *
     * @param mixed $user
     * @param string|array $permissions
     * @return bool
     */
    public function userHasPermission(mixed $user, string|array $permissions): bool
    {
        if (!method_exists($user, 'hasPermissionTo')) {
            return false;
        }

        return $user->hasPermissionTo($permissions);
    }
}
