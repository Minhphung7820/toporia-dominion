<?php

declare(strict_types=1);

namespace Toporia\Dominion\Middleware;

use Toporia\Framework\Http\Request;
use Toporia\Framework\Http\Response;
use Toporia\Dominion\DominionManager;
use Toporia\Dominion\Exceptions\UnauthorizedException;

/**
 * Class PermissionMiddleware
 *
 * Middleware to check if user has required permission(s).
 *
 * Usage in routes:
 * - ->middleware('permission:users.create')           // Single permission
 * - ->middleware('permission:users.create,users.read') // Any of these permissions
 *
 * @author      Phungtruong7820 <minhphung485@gmail.com>
 * @copyright   Copyright (c) 2025 Toporia Framework
 * @license     MIT
 */
final class PermissionMiddleware
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
     * @param string ...$permissions
     * @return Response
     * @throws UnauthorizedException
     */
    public function handle(Request $request, callable $next, string ...$permissions): Response
    {
        $user = auth()->user();

        if ($user === null) {
            throw UnauthorizedException::notLoggedIn();
        }

        // Parse permissions (support comma-separated format)
        $parsedPermissions = $this->parsePermissions($permissions);

        if (empty($parsedPermissions)) {
            return $next($request);
        }

        // Check if user has any of the required permissions
        if (!$this->rbac->userHasPermission($user, $parsedPermissions)) {
            throw UnauthorizedException::forPermissions($parsedPermissions);
        }

        return $next($request);
    }

    /**
     * Parse permissions from middleware parameters.
     *
     * @param array $permissions
     * @return array
     */
    private function parsePermissions(array $permissions): array
    {
        $parsed = [];

        foreach ($permissions as $permission) {
            // Support comma-separated permissions
            if (str_contains($permission, ',')) {
                $parsed = array_merge($parsed, explode(',', $permission));
            } else {
                $parsed[] = $permission;
            }
        }

        return array_map('trim', array_filter($parsed));
    }

    /**
     * Create middleware with permissions.
     *
     * @param string|array $permissions
     * @return string
     */
    public static function using(string|array $permissions): string
    {
        $permissionList = is_array($permissions) ? implode(',', $permissions) : $permissions;

        return 'permission:' . $permissionList;
    }
}
