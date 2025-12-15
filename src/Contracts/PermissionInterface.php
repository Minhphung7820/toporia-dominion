<?php

declare(strict_types=1);

namespace Toporia\Dominion\Contracts;

use Toporia\Framework\Support\Collection\Collection;

/**
 * Interface PermissionInterface
 *
 * Contract for Permission model implementations.
 *
 * @author      Phungtruong7820 <minhphung485@gmail.com>
 * @copyright   Copyright (c) 2025 Toporia Framework
 * @license     MIT
 */
interface PermissionInterface
{
    /**
     * Get the permission's unique name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get the permission's display name.
     *
     * @return string
     */
    public function getDisplayName(): string;

    /**
     * Get the permission's resource name.
     *
     * @return string|null
     */
    public function getResource(): ?string;

    /**
     * Get the permission's action name.
     *
     * @return string|null
     */
    public function getAction(): ?string;

    /**
     * Get all roles that have this permission.
     *
     * @return Collection
     */
    public function getRoles(): Collection;

    /**
     * Check if this is a system permission (cannot be deleted).
     *
     * @return bool
     */
    public function isSystem(): bool;

    /**
     * Check if the permission is active.
     *
     * @return bool
     */
    public function isActive(): bool;

    /**
     * Check if permission name matches a pattern (supports wildcards).
     *
     * @param string $pattern Pattern to match (e.g., "users.*")
     * @return bool
     */
    public function matches(string $pattern): bool;
}
