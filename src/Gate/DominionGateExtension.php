<?php

declare(strict_types=1);

namespace Toporia\Dominion\Gate;

use Toporia\Framework\Auth\Contracts\GateContract;
use Toporia\Dominion\DominionManager;

/**
 * Class DominionGateExtension
 *
 * Integrates RBAC with the authorization Gate.
 * Registers a `before` callback to check permissions via RBAC.
 *
 * This enables using gate methods like:
 * - can('users.create')
 * - authorize('posts.delete', $post)
 *
 * @author      Phungtruong7820 <minhphung485@gmail.com>
 * @copyright   Copyright (c) 2025 Toporia Framework
 * @license     MIT
 */
final class DominionGateExtension
{
    /**
     * RBAC manager.
     *
     * @var DominionManager
     */
    private DominionManager $rbac;

    /**
     * Create a new gate extension.
     *
     * @param DominionManager $rbac
     */
    public function __construct(DominionManager $rbac)
    {
        $this->rbac = $rbac;
    }

    /**
     * Register the RBAC gate extension.
     *
     * @param GateContract $gate
     * @return void
     */
    public function register(GateContract $gate): void
    {
        // Register before callback to check RBAC permissions
        $gate->before(function ($user, string $ability, array $arguments) {
            // Skip if no user
            if ($user === null) {
                return null;
            }

            // Super admin bypass - always allow everything
            if ($this->isSuperAdmin($user)) {
                return true;
            }

            // Check if user has the permission via RBAC
            if ($this->userHasPermission($user, $ability)) {
                return true;
            }

            // Return null to continue to policy/ability checks
            // This allows fallback to application-defined policies
            return null;
        });
    }

    /**
     * Check if user is a super admin.
     *
     * @param mixed $user
     * @return bool
     */
    private function isSuperAdmin(mixed $user): bool
    {
        return $this->rbac->isSuperAdmin($user);
    }

    /**
     * Check if user has a permission.
     *
     * @param mixed $user
     * @param string $permission
     * @return bool
     */
    private function userHasPermission(mixed $user, string $permission): bool
    {
        // Check if user model has HasRoles trait
        if (!method_exists($user, 'hasPermissionTo')) {
            return false;
        }

        return $user->hasPermissionTo($permission);
    }
}
