<?php

declare(strict_types=1);

namespace Toporia\Dominion\Exceptions;

use RuntimeException;

/**
 * Class DominionException
 *
 * Base exception for all RBAC-related errors.
 *
 * @author      Phungtruong7820 <minhphung485@gmail.com>
 * @copyright   Copyright (c) 2025 Toporia Framework
 * @license     MIT
 */
class DominionException extends RuntimeException
{
    /**
     * Create a new RBAC exception.
     *
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(
        string $message = 'An RBAC error occurred.',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
