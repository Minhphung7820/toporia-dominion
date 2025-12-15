<?php

declare(strict_types=1);

namespace Toporia\Dominion\Models;

use Toporia\Framework\Database\ORM\Model;
use Toporia\Framework\Database\ORM\Concerns\SoftDeletes;
use Toporia\Framework\Support\Collection\Collection;
use Toporia\Dominion\Contracts\RoleInterface;
use Toporia\Dominion\Contracts\PermissionInterface;
use Toporia\Dominion\Exceptions\PermissionNotFoundException;

/**
 * Class Role
 *
 * Represents a role in the RBAC system.
 *
 * @property int $id
 * @property string $name
 * @property string $display_name
 * @property string|null $description
 * @property int $level
 * @property int|null $parent_id
 * @property bool $is_system
 * @property bool $is_active
 * @property string $created_at
 * @property string $updated_at
 * @property string|null $deleted_at
 *
 * @author      Phungtruong7820 <minhphung485@gmail.com>
 * @copyright   Copyright (c) 2025 Toporia Framework
 * @license     MIT
 */
class Role extends Model implements RoleInterface
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'roles';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'level',
        'parent_id',
        'is_system',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'level' => 'integer',
        'parent_id' => 'integer',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * The default attribute values.
     *
     * @var array
     */
    protected $attributes = [
        'level' => 0,
        'is_system' => false,
        'is_active' => true,
    ];

    /**
     * Boot the model.
     *
     * @return void
     */
    protected static function boot(): void
    {
        parent::boot();

        // Auto-generate display_name from name if not provided
        static::creating(function (Role $role) {
            if (empty($role->display_name)) {
                $role->display_name = ucwords(str_replace(['-', '_'], ' ', $role->name));
            }
        });
    }

    /**
     * Get the role's unique name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the role's display name.
     *
     * @return string
     */
    public function getDisplayName(): string
    {
        return $this->display_name;
    }

    /**
     * Get the role's hierarchy level.
     *
     * @return int
     */
    public function getLevel(): int
    {
        return $this->level;
    }

    /**
     * Get the parent role.
     *
     * @return RoleInterface|null
     */
    public function getParent(): ?RoleInterface
    {
        return $this->parent;
    }

    /**
     * Get all permissions for this role.
     *
     * @return Collection
     */
    public function getPermissions(): Collection
    {
        return $this->permissions;
    }

    /**
     * Check if this is a system role.
     *
     * @return bool
     */
    public function isSystem(): bool
    {
        return $this->is_system;
    }

    /**
     * Check if the role is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Relationship: Parent role.
     *
     * @return \Toporia\Framework\Database\ORM\Relations\BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    /**
     * Relationship: Child roles.
     *
     * @return \Toporia\Framework\Database\ORM\Relations\HasMany
     */
    public function children()
    {
        return $this->hasMany(static::class, 'parent_id');
    }

    /**
     * Relationship: Permissions.
     *
     * @return \Toporia\Framework\Database\ORM\Relations\BelongsToMany
     */
    public function permissions()
    {
        $permissionClass = $this->getPermissionClass();
        $pivotTable = $this->getPivotTable('permission_role');

        return $this->belongsToMany($permissionClass, $pivotTable, 'role_id', 'permission_id')
            ->withTimestamps();
    }

    /**
     * Relationship: Users with this role.
     *
     * @return \Toporia\Framework\Database\ORM\Relations\BelongsToMany
     */
    public function users()
    {
        $userClass = $this->getUserClass();
        $pivotTable = $this->getPivotTable('role_user');

        return $this->belongsToMany($userClass, $pivotTable, 'role_id', 'user_id')
            ->withPivot('assigned_by', 'expires_at', 'is_active')
            ->withTimestamps();
    }

    /**
     * Check if role has a specific permission.
     *
     * @param string|PermissionInterface $permission
     * @return bool
     */
    public function hasPermissionTo(string|PermissionInterface $permission): bool
    {
        $permissionName = $permission instanceof PermissionInterface
            ? $permission->getName()
            : $permission;

        // Check direct permissions
        $hasDirectPermission = $this->permissions
            ->contains(fn($p) => $p->name === $permissionName || $this->matchesWildcard($p->name, $permissionName));

        if ($hasDirectPermission) {
            return true;
        }

        // Check inherited permissions from parent role
        if ($this->shouldInheritPermissions() && $this->parent !== null) {
            return $this->parent->hasPermissionTo($permission);
        }

        return false;
    }

    /**
     * Give permission(s) to this role.
     *
     * @param string|array|PermissionInterface ...$permissions
     * @return static
     */
    public function givePermissionTo(string|array|PermissionInterface ...$permissions): static
    {
        $permissionIds = $this->resolvePermissionIds($permissions);

        $this->permissions()->syncWithoutDetaching($permissionIds);

        // Clear cache
        $this->clearPermissionCache();

        return $this;
    }

    /**
     * Revoke permission(s) from this role.
     *
     * @param string|array|PermissionInterface ...$permissions
     * @return static
     */
    public function revokePermissionTo(string|array|PermissionInterface ...$permissions): static
    {
        $permissionIds = $this->resolvePermissionIds($permissions);

        $this->permissions()->detach($permissionIds);

        // Clear cache
        $this->clearPermissionCache();

        return $this;
    }

    /**
     * Sync permissions (replace all existing permissions).
     *
     * @param array $permissions
     * @return static
     */
    public function syncPermissions(array $permissions): static
    {
        $permissionIds = $this->resolvePermissionIds($permissions);

        $this->permissions()->sync($permissionIds);

        // Clear cache
        $this->clearPermissionCache();

        return $this;
    }

    /**
     * Set the parent role.
     *
     * @param Role|string|int|null $parent
     * @return static
     */
    public function setParent(Role|string|int|null $parent): static
    {
        if ($parent === null) {
            $this->parent_id = null;
        } elseif ($parent instanceof Role) {
            $this->parent_id = $parent->id;
        } elseif (is_string($parent)) {
            $parentRole = static::where('name', $parent)->first();
            $this->parent_id = $parentRole?->id;
        } else {
            $this->parent_id = $parent;
        }

        $this->save();

        return $this;
    }

    /**
     * Get all permissions including inherited ones.
     *
     * @return Collection
     */
    public function getAllPermissions(): Collection
    {
        $permissions = $this->permissions;

        if ($this->shouldInheritPermissions() && $this->parent !== null) {
            $parentPermissions = $this->parent->getAllPermissions();
            $permissions = $permissions->merge($parentPermissions)->unique('id');
        }

        return $permissions;
    }

    /**
     * Scope: Active roles only.
     *
     * @param \Toporia\Framework\Database\ORM\ModelQueryBuilder $query
     * @return \Toporia\Framework\Database\ORM\ModelQueryBuilder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Non-system roles only.
     *
     * @param \Toporia\Framework\Database\ORM\ModelQueryBuilder $query
     * @return \Toporia\Framework\Database\ORM\ModelQueryBuilder
     */
    public function scopeNonSystem($query)
    {
        return $query->where('is_system', false);
    }

    /**
     * Scope: Order by level (highest first).
     *
     * @param \Toporia\Framework\Database\ORM\ModelQueryBuilder $query
     * @return \Toporia\Framework\Database\ORM\ModelQueryBuilder
     */
    public function scopeOrderByLevel($query)
    {
        return $query->orderBy('level', 'DESC');
    }

    /**
     * Find a role by name.
     *
     * @param string $name
     * @return static|null
     */
    public static function findByName(string $name): ?static
    {
        return static::where('name', $name)->first();
    }

    /**
     * Find a role by name or fail.
     *
     * @param string $name
     * @return static
     * @throws \Toporia\Dominion\Exceptions\RoleNotFoundException
     */
    public static function findByNameOrFail(string $name): static
    {
        $role = static::findByName($name);

        if ($role === null) {
            throw \Toporia\Dominion\Exceptions\RoleNotFoundException::withName($name);
        }

        return $role;
    }

    /**
     * Find or create a role by name.
     *
     * @param string $name
     * @param array $attributes
     * @return static
     */
    public static function findOrCreate(string $name, array $attributes = []): static
    {
        $role = static::findByName($name);

        if ($role !== null) {
            return $role;
        }

        return static::create(array_merge(['name' => $name], $attributes));
    }

    /**
     * Resolve permission IDs from mixed input.
     *
     * @param array $permissions
     * @return array
     */
    protected function resolvePermissionIds(array $permissions): array
    {
        $ids = [];
        $permissionClass = $this->getPermissionClass();

        foreach ($permissions as $permissionArg) {
            if (is_array($permissionArg)) {
                $ids = array_merge($ids, $this->resolvePermissionIds($permissionArg));
                continue;
            }

            if ($permissionArg instanceof PermissionInterface) {
                $ids[] = $permissionArg->id;
                continue;
            }

            if (is_int($permissionArg)) {
                $ids[] = $permissionArg;
                continue;
            }

            if (is_string($permissionArg)) {
                $permission = $permissionClass::where('name', $permissionArg)->first();

                if ($permission === null) {
                    throw PermissionNotFoundException::withName($permissionArg);
                }

                $ids[] = $permission->id;
            }
        }

        return array_unique($ids);
    }

    /**
     * Check if wildcard pattern matches permission name.
     *
     * @param string $pattern
     * @param string $permissionName
     * @return bool
     */
    protected function matchesWildcard(string $pattern, string $permissionName): bool
    {
        if (!$this->wildcardEnabled()) {
            return false;
        }

        return fnmatch($pattern, $permissionName);
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
     * Check if permission inheritance is enabled.
     *
     * @return bool
     */
    protected function shouldInheritPermissions(): bool
    {
        return config('dominion.hierarchy.enabled', true)
            && config('dominion.hierarchy.inherit_permissions', true);
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
     * Get the user model class.
     *
     * @return string
     */
    protected function getUserClass(): string
    {
        return config('dominion.user_model')
            ?? config('auth.providers.users.model')
            ?? 'App\\Infrastructure\\Persistence\\Models\\UserModel';
    }

    /**
     * Get pivot table name.
     *
     * @param string $key
     * @return string
     */
    protected function getPivotTable(string $key): string
    {
        return config("rbac.tables.{$key}", $key);
    }

    /**
     * Clear permission cache for all users with this role.
     *
     * @return void
     */
    protected function clearPermissionCache(): void
    {
        if (function_exists('rbac')) {
            rbac()->clearCache();
        }
    }
}
