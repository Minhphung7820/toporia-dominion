<?php

declare(strict_types=1);

namespace Toporia\Dominion\Concerns;

use DateTimeInterface;
use Toporia\Framework\Support\Collection\Collection;
use Toporia\Dominion\Contracts\PermissionInterface;
use Toporia\Dominion\Exceptions\PermissionNotFoundException;
use Toporia\Dominion\Models\Permission;

/**
 * Trait HasPermissions
 *
 * Provides direct permission functionality to models.
 * This allows assigning permissions directly to users, bypassing roles.
 *
 * @author      Phungtruong7820 <minhphung485@gmail.com>
 * @copyright   Copyright (c) 2025 Toporia Framework
 * @license     MIT
 */
trait HasPermissions
{
    /**
     * Relationship: Direct permissions assigned to this model.
     *
     * @return \Toporia\Framework\Database\ORM\Relations\BelongsToMany
     */
    public function permissions()
    {
        $permissionClass = $this->getPermissionClass();
        $pivotTable = $this->getRbacPivotTable('permission_user');
        $userFk = config('dominion.foreign_keys.users', 'user_id');
        $permissionFk = config('dominion.foreign_keys.permissions', 'permission_id');

        return $this->belongsToMany($permissionClass, $pivotTable, $userFk, $permissionFk)
            ->withPivot('assigned_by', 'expires_at', 'is_active')
            ->withTimestamps();
    }

    /**
     * Get direct permissions assigned to this model.
     *
     * @return Collection
     */
    public function getDirectPermissions(): Collection
    {
        return $this->permissions->filter(function ($permission) {
            $pivot = $permission->pivot;

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

            // Check permission is_active
            if (!$permission->is_active) {
                return false;
            }

            return true;
        });
    }

    /**
     * Give direct permission(s) to this model.
     *
     * @param string|array|PermissionInterface ...$permissions
     * @return static
     */
    public function givePermissionTo(string|array|PermissionInterface ...$permissions): static
    {
        $permissionData = $this->preparePermissionData($permissions);

        foreach ($permissionData as $permissionId => $pivotData) {
            $this->permissions()->syncWithoutDetaching([$permissionId => $pivotData]);
        }

        $this->clearPermissionCache();

        return $this;
    }

    /**
     * Give a permission with expiration.
     *
     * @param string|PermissionInterface $permission
     * @param DateTimeInterface|string|null $expiresAt
     * @param int|null $assignedBy
     * @return static
     */
    public function givePermissionWithExpiration(
        string|PermissionInterface $permission,
        DateTimeInterface|string|null $expiresAt = null,
        ?int $assignedBy = null
    ): static {
        $permissionId = $this->resolvePermissionId($permission);

        $pivotData = [
            'is_active' => true,
            'expires_at' => $expiresAt instanceof DateTimeInterface
                ? $expiresAt->format('Y-m-d H:i:s')
                : $expiresAt,
        ];

        if ($assignedBy !== null) {
            $pivotData['assigned_by'] = $assignedBy;
        }

        $this->permissions()->syncWithoutDetaching([$permissionId => $pivotData]);
        $this->clearPermissionCache();

        return $this;
    }

    /**
     * Revoke direct permission(s) from this model.
     *
     * @param string|array|PermissionInterface ...$permissions
     * @return static
     */
    public function revokePermissionTo(string|array|PermissionInterface ...$permissions): static
    {
        $permissionIds = $this->resolvePermissionIds($permissions);

        $this->permissions()->detach($permissionIds);
        $this->clearPermissionCache();

        return $this;
    }

    /**
     * Sync direct permissions (replace all existing direct permissions).
     *
     * @param array $permissions
     * @return static
     */
    public function syncPermissions(array $permissions): static
    {
        $permissionData = $this->preparePermissionData($permissions);

        $this->permissions()->sync($permissionData);
        $this->clearPermissionCache();

        return $this;
    }

    /**
     * Check if model has a direct permission (not via role).
     *
     * @param string|PermissionInterface $permission
     * @return bool
     */
    public function hasDirectPermission(string|PermissionInterface $permission): bool
    {
        $permissionName = $permission instanceof PermissionInterface
            ? $permission->getName()
            : $permission;

        $directPermissions = $this->getDirectPermissions();

        // Direct name match
        if ($directPermissions->contains('name', $permissionName)) {
            return true;
        }

        // Wildcard pattern matching
        if ($this->wildcardEnabled()) {
            foreach ($directPermissions as $perm) {
                if (fnmatch($perm->name, $permissionName) || fnmatch($permissionName, $perm->name)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get permissions grouped by resource.
     *
     * @return Collection
     */
    public function getPermissionsByResource(): Collection
    {
        return $this->getAllPermissions()->groupBy('resource');
    }

    /**
     * Prepare permission data for sync operations.
     *
     * @param array $permissions
     * @return array
     */
    protected function preparePermissionData(array $permissions): array
    {
        $permissionData = [];
        $currentUserId = auth()->id();

        foreach ($permissions as $permissionArg) {
            if (is_array($permissionArg)) {
                $permissionData = array_merge($permissionData, $this->preparePermissionData($permissionArg));
                continue;
            }

            $permissionId = $this->resolvePermissionId($permissionArg);

            $permissionData[$permissionId] = [
                'is_active' => true,
                'assigned_by' => $currentUserId,
            ];
        }

        return $permissionData;
    }

    /**
     * Resolve a single permission to its ID.
     *
     * @param string|int|PermissionInterface $permission
     * @return int
     */
    protected function resolvePermissionId(string|int|PermissionInterface $permission): int
    {
        if ($permission instanceof PermissionInterface) {
            return $permission->id;
        }

        if (is_int($permission)) {
            return $permission;
        }

        $permissionClass = $this->getPermissionClass();
        $permissionModel = $permissionClass::where('name', $permission)->first();

        if ($permissionModel === null) {
            throw PermissionNotFoundException::withName($permission);
        }

        return $permissionModel->id;
    }

    /**
     * Resolve multiple permissions to their IDs.
     *
     * @param array $permissions
     * @return array
     */
    protected function resolvePermissionIds(array $permissions): array
    {
        $ids = [];

        foreach ($permissions as $permissionArg) {
            if (is_array($permissionArg)) {
                $ids = array_merge($ids, $this->resolvePermissionIds($permissionArg));
                continue;
            }

            $ids[] = $this->resolvePermissionId($permissionArg);
        }

        return array_unique($ids);
    }
}
