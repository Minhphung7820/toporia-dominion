<?php

declare(strict_types=1);

namespace Toporia\Dominion\Concerns;

use DateTimeInterface;
use Toporia\Framework\Support\Collection\Collection;
use Toporia\Dominion\Contracts\RoleInterface;
use Toporia\Dominion\Contracts\PermissionInterface;
use Toporia\Dominion\Exceptions\RoleNotFoundException;
use Toporia\Dominion\Models\Role;
use Toporia\Dominion\Models\Permission;

/**
 * Trait HasRoles
 *
 * Provides role and permission functionality to models.
 * Add this trait to your User model to enable RBAC.
 *
 * @author      Phungtruong7820 <minhphung485@gmail.com>
 * @copyright   Copyright (c) 2025 Toporia Framework
 * @license     MIT
 */
trait HasRoles
{
    use HasPermissions;

    /**
     * Cached permissions for performance.
     *
     * @var Collection|null
     */
    protected ?Collection $cachedPermissions = null;

    /**
     * Boot the HasRoles trait.
     *
     * @return void
     */
    public static function bootHasRoles(): void
    {
        static::deleting(function ($model) {
            if (method_exists($model, 'roles')) {
                $model->roles()->detach();
            }
            if (method_exists($model, 'permissions')) {
                $model->permissions()->detach();
            }
        });
    }

    /**
     * Relationship: Roles assigned to this model.
     *
     * @return \Toporia\Framework\Database\ORM\Relations\BelongsToMany
     */
    public function roles()
    {
        $roleClass = $this->getRoleClass();
        $pivotTable = $this->getRbacPivotTable('role_user');
        $userFk = config('dominion.foreign_keys.users', 'user_id');
        $roleFk = config('dominion.foreign_keys.roles', 'role_id');

        return $this->belongsToMany($roleClass, $pivotTable, $userFk, $roleFk)
            ->withPivot('assigned_by', 'expires_at', 'is_active')
            ->withTimestamps();
    }

    /**
     * Get all roles assigned to this model.
     *
     * @return Collection
     */
    public function getRoles(): Collection
    {
        return $this->roles;
    }

    /**
     * Get all role names.
     *
     * @return Collection
     */
    public function getRoleNames(): Collection
    {
        return $this->roles->pluck('name');
    }

    /**
     * Assign role(s) to this model.
     *
     * @param string|array|RoleInterface ...$roles
     * @return static
     */
    public function assignRole(string|array|RoleInterface ...$roles): static
    {
        $roleData = $this->prepareRoleData($roles);

        foreach ($roleData as $roleId => $pivotData) {
            $this->roles()->syncWithoutDetaching([$roleId => $pivotData]);
        }

        $this->clearPermissionCache();

        return $this;
    }

    /**
     * Assign a role with expiration.
     *
     * @param string|RoleInterface $role
     * @param DateTimeInterface|string|null $expiresAt
     * @param int|null $assignedBy
     * @return static
     */
    public function assignRoleWithExpiration(
        string|RoleInterface $role,
        DateTimeInterface|string|null $expiresAt = null,
        ?int $assignedBy = null
    ): static {
        $roleId = $this->resolveRoleId($role);

        $pivotData = [
            'is_active' => true,
            'expires_at' => $expiresAt instanceof DateTimeInterface
                ? $expiresAt->format('Y-m-d H:i:s')
                : $expiresAt,
        ];

        if ($assignedBy !== null) {
            $pivotData['assigned_by'] = $assignedBy;
        }

        $this->roles()->syncWithoutDetaching([$roleId => $pivotData]);
        $this->clearPermissionCache();

        return $this;
    }

    /**
     * Remove role(s) from this model.
     *
     * @param string|array|RoleInterface ...$roles
     * @return static
     */
    public function removeRole(string|array|RoleInterface ...$roles): static
    {
        $roleIds = $this->resolveRoleIds($roles);

        $this->roles()->detach($roleIds);
        $this->clearPermissionCache();

        return $this;
    }

    /**
     * Sync roles (replace all existing roles).
     *
     * @param array $roles
     * @return static
     */
    public function syncRoles(array $roles): static
    {
        $roleData = $this->prepareRoleData($roles);

        $this->roles()->sync($roleData);
        $this->clearPermissionCache();

        return $this;
    }

    /**
     * Check if model has a specific role.
     *
     * @param string|array|RoleInterface $roles
     * @return bool
     */
    public function hasRole(string|array|RoleInterface $roles): bool
    {
        if (is_string($roles)) {
            return $this->getActiveRoles()->contains('name', $roles);
        }

        if ($roles instanceof RoleInterface) {
            return $this->getActiveRoles()->contains('id', $roles->id);
        }

        // Array: check if has any of the given roles
        foreach ($roles as $roleName) {
            if ($this->hasRole($roleName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if model has all of the given roles.
     *
     * @param array $roles
     * @return bool
     */
    public function hasAllRoles(array $roles): bool
    {
        foreach ($roles as $role) {
            if (!$this->hasRole($role)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if model has any of the given roles.
     *
     * @param array $roles
     * @return bool
     */
    public function hasAnyRole(array $roles): bool
    {
        return $this->hasRole($roles);
    }

    /**
     * Check if model has exactly the given roles.
     *
     * @param array $roles
     * @return bool
     */
    public function hasExactRoles(array $roles): bool
    {
        $currentRoles = $this->getRoleNames()->sort()->values()->all();
        $expectedRoles = (new Collection($roles))->sort()->values()->all();

        return $currentRoles === $expectedRoles;
    }

    /**
     * Get all permissions (from roles and direct assignments).
     *
     * @return Collection
     */
    public function getAllPermissions(): Collection
    {
        if ($this->cachedPermissions !== null) {
            return $this->cachedPermissions;
        }

        // Get permissions from roles
        $rolePermissions = $this->getActiveRoles()
            ->flatMap(fn($role) => $role->getAllPermissions())
            ->unique('id');

        // Get direct permissions
        $directPermissions = $this->getDirectPermissions();

        // Merge and deduplicate
        $this->cachedPermissions = $rolePermissions
            ->merge($directPermissions)
            ->unique('id')
            ->values();

        return $this->cachedPermissions;
    }

    /**
     * Get all permission names.
     *
     * @return Collection
     */
    public function getPermissionNames(): Collection
    {
        return $this->getAllPermissions()->pluck('name');
    }

    /**
     * Check if model has a specific permission.
     *
     * @param string|array|PermissionInterface $permission
     * @return bool
     */
    public function hasPermissionTo(string|array|PermissionInterface $permission): bool
    {
        // Super admin check
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (is_array($permission)) {
            foreach ($permission as $perm) {
                if ($this->hasPermissionTo($perm)) {
                    return true;
                }
            }
            return false;
        }

        $permissionName = $permission instanceof PermissionInterface
            ? $permission->getName()
            : $permission;

        // Wildcard check for '*' (super permission)
        if ($permissionName === '*') {
            return $this->isSuperAdmin();
        }

        // Check in cached permissions
        $permissions = $this->getAllPermissions();

        // Direct name match
        if ($permissions->contains('name', $permissionName)) {
            return true;
        }

        // Wildcard pattern matching
        if ($this->wildcardEnabled()) {
            foreach ($permissions as $perm) {
                if (fnmatch($perm->name, $permissionName) || fnmatch($permissionName, $perm->name)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if model has all of the given permissions.
     *
     * @param array $permissions
     * @return bool
     */
    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermissionTo($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if model has any of the given permissions.
     *
     * @param array $permissions
     * @return bool
     */
    public function hasAnyPermission(array $permissions): bool
    {
        return $this->hasPermissionTo($permissions);
    }

    /**
     * Check if model has permission via a role.
     *
     * @param string|PermissionInterface $permission
     * @return bool
     */
    public function hasPermissionViaRole(string|PermissionInterface $permission): bool
    {
        $permissionName = $permission instanceof PermissionInterface
            ? $permission->getName()
            : $permission;

        foreach ($this->getActiveRoles() as $role) {
            if ($role->hasPermissionTo($permissionName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the highest level role for this user.
     *
     * @return Role|null
     */
    public function getHighestRole(): ?Role
    {
        return $this->getActiveRoles()
            ->sortByDesc('level')
            ->first();
    }

    /**
     * Check if user is a super admin.
     *
     * @return bool
     */
    public function isSuperAdmin(): bool
    {
        if (!config('dominion.super_admin.enabled', true)) {
            return false;
        }

        $superAdminRole = config('dominion.super_admin.role', 'super-admin');

        return $this->hasRole($superAdminRole);
    }

    /**
     * Get active roles (not expired, is_active = true).
     *
     * @return Collection
     */
    protected function getActiveRoles(): Collection
    {
        return $this->roles->filter(function ($role) {
            $pivot = $role->pivot;

            // Check is_active
            if (isset($pivot->is_active) && !$pivot->is_active) {
                return false;
            }

            // Check expiration
            if (isset($pivot->expires_at) && $pivot->expires_at !== null) {
                $expiresAt = is_string($pivot->expires_at)
                    ? new \DateTime($pivot->expires_at)
                    : $pivot->expires_at;

                if ($expiresAt < new \DateTime()) {
                    return false;
                }
            }

            // Check role is_active
            if (!$role->is_active) {
                return false;
            }

            return true;
        });
    }

    /**
     * Prepare role data for sync operations.
     *
     * @param array $roles
     * @return array
     */
    protected function prepareRoleData(array $roles): array
    {
        $roleData = [];
        $currentUserId = auth()->id();

        foreach ($roles as $roleArg) {
            if (is_array($roleArg)) {
                $roleData = array_merge($roleData, $this->prepareRoleData($roleArg));
                continue;
            }

            $roleId = $this->resolveRoleId($roleArg);

            $roleData[$roleId] = [
                'is_active' => true,
                'assigned_by' => $currentUserId,
            ];
        }

        return $roleData;
    }

    /**
     * Resolve a single role to its ID.
     *
     * @param string|int|RoleInterface $role
     * @return int
     */
    protected function resolveRoleId(string|int|RoleInterface $role): int
    {
        if ($role instanceof RoleInterface) {
            return $role->id;
        }

        if (is_int($role)) {
            return $role;
        }

        $roleClass = $this->getRoleClass();
        $roleModel = $roleClass::where('name', $role)->first();

        if ($roleModel === null) {
            throw RoleNotFoundException::withName($role);
        }

        return $roleModel->id;
    }

    /**
     * Resolve multiple roles to their IDs.
     *
     * @param array $roles
     * @return array
     */
    protected function resolveRoleIds(array $roles): array
    {
        $ids = [];

        foreach ($roles as $roleArg) {
            if (is_array($roleArg)) {
                $ids = array_merge($ids, $this->resolveRoleIds($roleArg));
                continue;
            }

            $ids[] = $this->resolveRoleId($roleArg);
        }

        return array_unique($ids);
    }

    /**
     * Clear the permission cache for this model.
     *
     * @return void
     */
    protected function clearPermissionCache(): void
    {
        $this->cachedPermissions = null;

        // Clear global cache
        if (function_exists('rbac')) {
            rbac()->clearCache($this->getAuthIdentifier());
        }
    }

    /**
     * Check if wildcard permissions are enabled.
     *
     * @return bool
     */
    protected function wildcardEnabled(): bool
    {
        return config('dominion.wildcards.enabled', true);
    }

    /**
     * Get the role model class.
     *
     * @return string
     */
    protected function getRoleClass(): string
    {
        return config('dominion.models.role', Role::class);
    }

    /**
     * Get the permission model class.
     *
     * @return string
     */
    protected function getPermissionClass(): string
    {
        return config('dominion.models.permission', Permission::class);
    }

    /**
     * Get pivot table name.
     *
     * @param string $key
     * @return string
     */
    protected function getRbacPivotTable(string $key): string
    {
        return config("rbac.tables.{$key}", $key);
    }
}
