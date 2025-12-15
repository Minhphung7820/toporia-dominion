<?php

declare(strict_types=1);

namespace Toporia\Dominion\Exceptions;

/**
 * Class RoleAlreadyExistsException
 *
 * Exception thrown when attempting to create a role that already exists.
 *
 * @author      Phungtruong7820 <minhphung485@gmail.com>
 * @copyright   Copyright (c) 2025 Toporia Framework
 * @license     MIT
 */
final class RoleAlreadyExistsException extends DominionException
{
    /**
     * Create exception for a role name.
     *
     * @param string $name
     * @return static
     */
    public static function withName(string $name): static
    {
        return new static("A role with name '{$name}' already exists.");
    }
}
