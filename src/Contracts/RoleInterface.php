<?php

declare(strict_types=1);

namespace Toporia\Dominion\Contracts;

use Toporia\Framework\Support\Collection\Collection;

/**
 * Interface RoleInterface
 *
 * Contract for Role model implementations.
 *
 * @author      Phungtruong7820 <minhphung485@gmail.com>
 * @copyright   Copyright (c) 2025 Toporia Framework
 * @license     MIT
 */
interface RoleInterface
{
    /**
     * Get the role's unique name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get the role's display name.
     *
     * @return string
     */
    public function getDisplayName(): string;

    /**
     * Get the role's hierarchy level.
     *
     * @return int
     */
    public function getLevel(): int;

    /**
     * Get the parent role.
     *
     * @return RoleInterface|null
     */
    public function getParent(): ?RoleInterface;

    /**
     * Get all permissions for this role.
     *
     * @return Collection
     */
    public function getPermissions(): Collection;

    /**
     * Check if role has a specific permission.
     *
     * @param string|PermissionInterface $permission
     * @return bool
     */
    public function hasPermissionTo(string|PermissionInterface $permission): bool;

    /**
     * Give permission(s) to this role.
     *
     * @param string|array|PermissionInterface ...$permissions
     * @return static
     */
    public function givePermissionTo(string|array|PermissionInterface ...$permissions): static;

    /**
     * Revoke permission(s) from this role.
     *
     * @param string|array|PermissionInterface ...$permissions
     * @return static
     */
    public function revokePermissionTo(string|array|PermissionInterface ...$permissions): static;

    /**
     * Sync permissions (replace all existing permissions).
     *
     * @param array $permissions
     * @return static
     */
    public function syncPermissions(array $permissions): static;

    /**
     * Check if this is a system role (cannot be deleted).
     *
     * @return bool
     */
    public function isSystem(): bool;

    /**
     * Check if the role is active.
     *
     * @return bool
     */
    public function isActive(): bool;
}
