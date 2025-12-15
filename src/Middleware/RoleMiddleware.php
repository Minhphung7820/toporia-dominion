<?php

declare(strict_types=1);

namespace Toporia\Dominion\Middleware;

use Toporia\Framework\Http\Request;
use Toporia\Framework\Http\Response;
use Toporia\Dominion\DominionManager;
use Toporia\Dominion\Exceptions\UnauthorizedException;

/**
 * Class RoleMiddleware
 *
 * Middleware to check if user has required role(s).
 *
 * Usage in routes:
 * - ->middleware('role:admin')           // Single role
 * - ->middleware('role:admin,editor')    // Any of these roles
 *
 * @author      Phungtruong7820 <minhphung485@gmail.com>
 * @copyright   Copyright (c) 2025 Toporia Framework
 * @license     MIT
 */
final class RoleMiddleware
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
     * @param string ...$roles
     * @return Response
     * @throws UnauthorizedException
     */
    public function handle(Request $request, callable $next, string ...$roles): Response
    {
        $user = auth()->user();

        if ($user === null) {
            throw UnauthorizedException::notLoggedIn();
        }

        // Parse roles (support comma-separated format)
        $parsedRoles = $this->parseRoles($roles);

        if (empty($parsedRoles)) {
            return $next($request);
        }

        // Check if user has any of the required roles
        if (!$this->rbac->userHasRole($user, $parsedRoles)) {
            throw UnauthorizedException::forRoles($parsedRoles);
        }

        return $next($request);
    }

    /**
     * Parse roles from middleware parameters.
     *
     * @param array $roles
     * @return array
     */
    private function parseRoles(array $roles): array
    {
        $parsed = [];

        foreach ($roles as $role) {
            // Support comma-separated roles
            if (str_contains($role, ',')) {
                $parsed = array_merge($parsed, explode(',', $role));
            } else {
                $parsed[] = $role;
            }
        }

        return array_map('trim', array_filter($parsed));
    }

    /**
     * Create middleware with roles.
     *
     * @param string|array $roles
     * @return string
     */
    public static function using(string|array $roles): string
    {
        $roleList = is_array($roles) ? implode(',', $roles) : $roles;

        return 'role:' . $roleList;
    }
}
