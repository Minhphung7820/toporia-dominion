<?php

declare(strict_types=1);

namespace Toporia\Dominion\Exceptions;

/**
 * Class PermissionAlreadyExistsException
 *
 * Exception thrown when attempting to create a permission that already exists.
 *
 * @author      Phungtruong7820 <minhphung485@gmail.com>
 * @copyright   Copyright (c) 2025 Toporia Framework
 * @license     MIT
 */
final class PermissionAlreadyExistsException extends DominionException
{
    /**
     * Create exception for a permission name.
     *
     * @param string $name
     * @return static
     */
    public static function withName(string $name): static
    {
        return new static("A permission with name '{$name}' already exists.");
    }
}
