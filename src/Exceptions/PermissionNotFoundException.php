<?php

declare(strict_types=1);

namespace Toporia\Dominion\Exceptions;

/**
 * Class PermissionNotFoundException
 *
 * Exception thrown when a permission cannot be found.
 *
 * @author      Phungtruong7820 <minhphung485@gmail.com>
 * @copyright   Copyright (c) 2025 Toporia Framework
 * @license     MIT
 */
final class PermissionNotFoundException extends DominionException
{
    /**
     * Create exception for a permission name.
     *
     * @param string $name
     * @return static
     */
    public static function withName(string $name): static
    {
        return new static("Permission '{$name}' not found.");
    }

    /**
     * Create exception for a permission ID.
     *
     * @param int|string $id
     * @return static
     */
    public static function withId(int|string $id): static
    {
        return new static("Permission with ID '{$id}' not found.");
    }

    /**
     * Create exception for multiple permissions.
     *
     * @param array $names
     * @return static
     */
    public static function withNames(array $names): static
    {
        $nameList = implode(', ', $names);
        return new static("One or more permissions not found: {$nameList}");
    }
}
