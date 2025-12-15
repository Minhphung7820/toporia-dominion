<?php

declare(strict_types=1);

namespace Toporia\Dominion\Models;

use Toporia\Framework\Database\ORM\Model;
use Toporia\Framework\Support\Collection\Collection;
use Toporia\Dominion\Contracts\PermissionInterface;

/**
 * Class Permission
 *
 * Represents a permission in the RBAC system.
 *
 * @property int $id
 * @property string $name
 * @property string $display_name
 * @property string|null $description
 * @property string|null $resource
 * @property string|null $action
 * @property bool $is_system
 * @property bool $is_active
 * @property string $created_at
 * @property string $updated_at
 *
 * @author      Phungtruong7820 <minhphung485@gmail.com>
 * @copyright   Copyright (c) 2025 Toporia Framework
 * @license     MIT
 */
class Permission extends Model implements PermissionInterface
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'permissions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'resource',
        'action',
        'is_system',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * The default attribute values.
     *
     * @var array
     */
    protected $attributes = [
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

        // Auto-generate display_name and parse resource/action from name
        static::creating(function (Permission $permission) {
            if (empty($permission->display_name)) {
                $permission->display_name = ucwords(str_replace(['-', '_', '.'], ' ', $permission->name));
            }

            // Parse resource and action from name (e.g., "users.create")
            if (empty($permission->resource) || empty($permission->action)) {
                $separator = config('dominion.wildcards.separator', '.');
                $parts = explode($separator, $permission->name);

                if (count($parts) >= 2) {
                    $permission->resource = $permission->resource ?? $parts[0];
                    $permission->action = $permission->action ?? $parts[1];
                }
            }
        });
    }

    /**
     * Get the permission's unique name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the permission's display name.
     *
     * @return string
     */
    public function getDisplayName(): string
    {
        return $this->display_name;
    }

    /**
     * Get the permission's resource name.
     *
     * @return string|null
     */
    public function getResource(): ?string
    {
        return $this->resource;
    }

    /**
     * Get the permission's action name.
     *
     * @return string|null
     */
    public function getAction(): ?string
    {
        return $this->action;
    }

    /**
     * Get all roles that have this permission.
     *
     * @return Collection
     */
    public function getRoles(): Collection
    {
        return $this->roles;
    }

    /**
     * Check if this is a system permission.
     *
     * @return bool
     */
    public function isSystem(): bool
    {
        return $this->is_system;
    }

    /**
     * Check if the permission is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Check if permission name matches a pattern (supports wildcards).
     *
     * @param string $pattern Pattern to match (e.g., "users.*")
     * @return bool
     */
    public function matches(string $pattern): bool
    {
        if (!$this->wildcardEnabled()) {
            return $this->name === $pattern;
        }

        return fnmatch($pattern, $this->name);
    }

    /**
     * Relationship: Roles.
     *
     * @return \Toporia\Framework\Database\ORM\Relations\BelongsToMany
     */
    public function roles()
    {
        $roleClass = $this->getRoleClass();
        $pivotTable = $this->getPivotTable('permission_role');

        return $this->belongsToMany($roleClass, $pivotTable, 'permission_id', 'role_id')
            ->withTimestamps();
    }

    /**
     * Relationship: Users with direct permission.
     *
     * @return \Toporia\Framework\Database\ORM\Relations\BelongsToMany
     */
    public function users()
    {
        $userClass = $this->getUserClass();
        $pivotTable = $this->getPivotTable('permission_user');

        return $this->belongsToMany($userClass, $pivotTable, 'permission_id', 'user_id')
            ->withPivot('assigned_by', 'expires_at', 'is_active')
            ->withTimestamps();
    }

    /**
     * Scope: Active permissions only.
     *
     * @param \Toporia\Framework\Database\ORM\ModelQueryBuilder $query
     * @return \Toporia\Framework\Database\ORM\ModelQueryBuilder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Non-system permissions only.
     *
     * @param \Toporia\Framework\Database\ORM\ModelQueryBuilder $query
     * @return \Toporia\Framework\Database\ORM\ModelQueryBuilder
     */
    public function scopeNonSystem($query)
    {
        return $query->where('is_system', false);
    }

    /**
     * Scope: Filter by resource.
     *
     * @param \Toporia\Framework\Database\ORM\ModelQueryBuilder $query
     * @param string $resource
     * @return \Toporia\Framework\Database\ORM\ModelQueryBuilder
     */
    public function scopeForResource($query, string $resource)
    {
        return $query->where('resource', $resource);
    }

    /**
     * Scope: Filter by action.
     *
     * @param \Toporia\Framework\Database\ORM\ModelQueryBuilder $query
     * @param string $action
     * @return \Toporia\Framework\Database\ORM\ModelQueryBuilder
     */
    public function scopeForAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Find a permission by name.
     *
     * @param string $name
     * @return static|null
     */
    public static function findByName(string $name): ?static
    {
        return static::where('name', $name)->first();
    }

    /**
     * Find a permission by name or fail.
     *
     * @param string $name
     * @return static
     * @throws \Toporia\Dominion\Exceptions\PermissionNotFoundException
     */
    public static function findByNameOrFail(string $name): static
    {
        $permission = static::findByName($name);

        if ($permission === null) {
            throw \Toporia\Dominion\Exceptions\PermissionNotFoundException::withName($name);
        }

        return $permission;
    }

    /**
     * Find or create a permission by name.
     *
     * @param string $name
     * @param array $attributes
     * @return static
     */
    public static function findOrCreate(string $name, array $attributes = []): static
    {
        $permission = static::findByName($name);

        if ($permission !== null) {
            return $permission;
        }

        return static::create(array_merge(['name' => $name], $attributes));
    }

    /**
     * Get all unique resources.
     *
     * @return Collection
     */
    public static function getResources(): Collection
    {
        return static::query()
            ->select('resource')
            ->whereNotNull('resource')
            ->distinct()
            ->get()
            ->pluck('resource');
    }

    /**
     * Get all actions for a resource.
     *
     * @param string $resource
     * @return Collection
     */
    public static function getActionsForResource(string $resource): Collection
    {
        return static::forResource($resource)
            ->select('action')
            ->whereNotNull('action')
            ->distinct()
            ->get()
            ->pluck('action');
    }

    /**
     * Create CRUD permissions for a resource.
     *
     * @param string $resource
     * @param array $actions
     * @return Collection
     */
    public static function createCrudPermissions(
        string $resource,
        array $actions = ['create', 'read', 'update', 'delete']
    ): Collection {
        $separator = config('dominion.wildcards.separator', '.');
        $permissions = new Collection();

        foreach ($actions as $action) {
            $name = "{$resource}{$separator}{$action}";
            $permissions->push(static::findOrCreate($name, [
                'resource' => $resource,
                'action' => $action,
            ]));
        }

        return $permissions;
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
}
