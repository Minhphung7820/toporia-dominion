<?php

declare(strict_types=1);

namespace Toporia\Dominion\Middleware;

use Toporia\Framework\Http\Request;
use Toporia\Framework\Http\Response;
use Toporia\Dominion\DominionManager;
use Toporia\Dominion\Exceptions\UnauthorizedException;

/**
 * Class RoleOrPermissionMiddleware
 *
 * Middleware to check if user has required role(s) OR permission(s).
 *
 * Usage in routes:
 * - ->middleware('role_or_permission:admin|users.create')
 * - ->middleware('role_or_permission:admin,editor|users.create,users.update')
 *
 * Format: roles|permissions (separated by pipe)
 *
 * @author      Phungtruong7820 <minhphung485@gmail.com>
 * @copyright   Copyright (c) 2025 Toporia Framework
 * @license     MIT
 */
final class RoleOrPermissionMiddleware
{
    /**
     * RBAC manager.
     *
     * @var DominionManager
     */
    private DominionManager $rbac;

    /**
     * Create a new middleware instance.
     *
     * @param DominionManager $rbac
     */
    public function __construct(DominionManager $rbac)
    {
        $this->rbac = $rbac;
    }

    /**
     * Handle the request.
     *
     * @param Request $request
     * @param callable $next
     * @param string ...$rolesOrPermissions
     * @return Response
     * @throws UnauthorizedException
     */
    public function handle(Request $request, callable $next, string ...$rolesOrPermissions): Response
    {
        $user = auth()->user();

        if ($user === null) {
            throw UnauthorizedException::notLoggedIn();
        }

        // Parse roles and permissions
        [$roles, $permissions] = $this->parseRolesAndPermissions($rolesOrPermissions);

        if (empty($roles) && empty($permissions)) {
            return $next($request);
        }

        // Check if user has any of the required roles OR permissions
        $hasRole = !empty($roles) && $this->rbac->userHasRole($user, $roles);
        $hasPermission = !empty($permissions) && $this->rbac->userHasPermission($user, $permissions);

        if (!$hasRole && !$hasPermission) {
            throw UnauthorizedException::forRolesOrPermissions($roles, $permissions);
        }

        return $next($request);
    }

    /**
     * Parse roles and permissions from middleware parameters.
     *
     * @param array $rolesOrPermissions
     * @return array [roles, permissions]
     */
    private function parseRolesAndPermissions(array $rolesOrPermissions): array
    {
        $roles = [];
        $permissions = [];

        foreach ($rolesOrPermissions as $param) {
            // Format: roles|permissions
            if (str_contains($param, '|')) {
                [$rolesPart, $permissionsPart] = explode('|', $param, 2);

                if (!empty($rolesPart)) {
                    $roles = array_merge($roles, $this->parseList($rolesPart));
                }

                if (!empty($permissionsPart)) {
                    $permissions = array_merge($permissions, $this->parseList($permissionsPart));
                }
            } else {
                // Determine if it's a role or permission by checking for dot separator
                if (str_contains($param, '.')) {
                    $permissions = array_merge($permissions, $this->parseList($param));
                } else {
                    $roles = array_merge($roles, $this->parseList($param));
                }
            }
        }

        return [
            array_unique(array_filter($roles)),
            array_unique(array_filter($permissions)),
        ];
    }

    /**
     * Parse a comma-separated list.
     *
     * @param string $list
     * @return array
     */
    private function parseList(string $list): array
    {
        return array_map('trim', explode(',', $list));
    }

    /**
     * Create middleware with roles and/or permissions.
     *
     * @param array $roles
     * @param array $permissions
     * @return string
     */
    public static function using(array $roles = [], array $permissions = []): string
    {
        $roleList = implode(',', $roles);
        $permissionList = implode(',', $permissions);

        return 'role_or_permission:' . $roleList . '|' . $permissionList;
    }
}
