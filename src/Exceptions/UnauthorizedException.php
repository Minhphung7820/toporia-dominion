<?php

declare(strict_types=1);

namespace Toporia\Dominion\Exceptions;

use Toporia\Framework\Http\JsonResponse;
use Toporia\Framework\Http\Response;

/**
 * Class UnauthorizedException
 *
 * Exception thrown when authorization fails.
 *
 * @author      Phungtruong7820 <minhphung485@gmail.com>
 * @copyright   Copyright (c) 2025 Toporia Framework
 * @license     MIT
 */
final class UnauthorizedException extends DominionException
{
    /**
     * Required roles for this action.
     *
     * @var array
     */
    private array $requiredRoles = [];

    /**
     * Required permissions for this action.
     *
     * @var array
     */
    private array $requiredPermissions = [];

    /**
     * Create a new unauthorized exception.
     *
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(
        string $message = 'User does not have the required authorization.',
        int $code = 403,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create exception for missing roles.
     *
     * @param array $roles
     * @return static
     */
    public static function forRoles(array $roles): static
    {
        $roleList = implode(', ', $roles);
        $exception = new static("User does not have any of the required roles: {$roleList}");
        $exception->requiredRoles = $roles;

        return $exception;
    }

    /**
     * Create exception for missing permissions.
     *
     * @param array $permissions
     * @return static
     */
    public static function forPermissions(array $permissions): static
    {
        $permissionList = implode(', ', $permissions);
        $exception = new static("User does not have any of the required permissions: {$permissionList}");
        $exception->requiredPermissions = $permissions;

        return $exception;
    }

    /**
     * Create exception for missing role or permission.
     *
     * @param array $roles
     * @param array $permissions
     * @return static
     */
    public static function forRolesOrPermissions(array $roles, array $permissions): static
    {
        $roleList = implode(', ', $roles);
        $permissionList = implode(', ', $permissions);
        $exception = new static(
            "User does not have any of the required roles ({$roleList}) or permissions ({$permissionList})."
        );
        $exception->requiredRoles = $roles;
        $exception->requiredPermissions = $permissions;

        return $exception;
    }

    /**
     * Create exception for unauthenticated user.
     *
     * @return static
     */
    public static function notLoggedIn(): static
    {
        return new static('User is not logged in.', 401);
    }

    /**
     * Get the required roles.
     *
     * @return array
     */
    public function getRequiredRoles(): array
    {
        return $this->requiredRoles;
    }

    /**
     * Get the required permissions.
     *
     * @return array
     */
    public function getRequiredPermissions(): array
    {
        return $this->requiredPermissions;
    }

    /**
     * Convert exception to HTTP response.
     *
     * @return JsonResponse
     */
    public function toResponse(): JsonResponse
    {
        return response()->json([
            'error' => 'unauthorized',
            'message' => $this->getMessage(),
            'required_roles' => $this->requiredRoles,
            'required_permissions' => $this->requiredPermissions,
        ], $this->getCode());
    }
}
