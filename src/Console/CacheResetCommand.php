<?php

declare(strict_types=1);

namespace Toporia\Dominion\Console;

use Toporia\Framework\Console\Command;
use Toporia\Dominion\DominionManager;

/**
 * Class CacheResetCommand
 *
 * Console command to reset the RBAC permission cache.
 *
 * Usage: php console rbac:cache-reset [--user=]
 *
 * @author      Phungtruong7820 <minhphung485@gmail.com>
 * @copyright   Copyright (c) 2025 Toporia Framework
 * @license     MIT
 */
final class CacheResetCommand extends Command
{
    /**
     * The command signature.
     *
     * @var string
     */
    protected string $signature = 'rbac:cache-reset {--user= : Clear cache for specific user ID only}';

    /**
     * The command description.
     *
     * @var string
     */
    protected string $description = 'Reset the RBAC permission cache';

    /**
     * RBAC manager.
     *
     * @var DominionManager
     */
    private DominionManager $rbac;

    /**
     * Create a new command instance.
     *
     * @param DominionManager $rbac
     */
    public function __construct(DominionManager $rbac)
    {
        parent::__construct();
        $this->rbac = $rbac;
    }

    /**
     * Execute the command.
     *
     * @return int
     */
    public function handle(): int
    {
        $userId = $this->option('user');

        try {
            if ($userId !== null) {
                $this->rbac->clearCache((int) $userId);
                $this->info("RBAC cache cleared for user #{$userId}.");
            } else {
                $this->rbac->clearCache();
                $this->info('All RBAC caches have been cleared.');
            }

            return 0;
        } catch (\Throwable $e) {
            $this->error("Failed to clear cache: {$e->getMessage()}");
            return 1;
        }
    }
}
