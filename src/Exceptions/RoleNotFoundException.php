<?php

declare(strict_types=1);

namespace Toporia\Dominion\Exceptions;

/**
 * Class RoleNotFoundException
 *
 * Exception thrown when a role cannot be found.
 *
 * @author      Phungtruong7820 <minhphung485@gmail.com>
 * @copyright   Copyright (c) 2025 Toporia Framework
 * @license     MIT
 */
final class RoleNotFoundException extends DominionException
{
    /**
     * Create exception for a role name.
     *
     * @param string $name
     * @return static
     */
    public static function withName(string $name): static
    {
        return new static("Role '{$name}' not found.");
    }

    /**
     * Create exception for a role ID.
     *
     * @param int|string $id
     * @return static
     */
    public static function withId(int|string $id): static
    {
        return new static("Role with ID '{$id}' not found.");
    }

    /**
     * Create exception for multiple roles.
     *
     * @param array $names
     * @return static
     */
    public static function withNames(array $names): static
    {
        $nameList = implode(', ', $names);
        return new static("One or more roles not found: {$nameList}");
    }
}
