<?php

declare(strict_types=1);

namespace Toporia\Dominion\Console;

use Toporia\Framework\Console\Command;
use Toporia\Dominion\DominionManager;

/**
 * Class CreateCrudPermissionsCommand
 *
 * Console command to create CRUD permissions for a resource.
 *
 * Usage: php console rbac:make-crud {resource} [--actions=create,read,update,delete]
 *
 * @author      Phungtruong7820 <minhphung485@gmail.com>
 * @copyright   Copyright (c) 2025 Toporia Framework
 * @license     MIT
 */
final class CreateCrudPermissionsCommand extends Command
{
    /**
     * The command signature.
     *
     * @var string
     */
    protected string $signature = 'rbac:make-crud {resource : Resource name (e.g., users, posts)} {--actions=create,read,update,delete : Comma-separated actions}';

    /**
     * The command description.
     *
     * @var string
     */
    protected string $description = 'Create CRUD permissions for a resource';

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
        $resource = $this->argument('resource');
        $actionsString = $this->option('actions');
        $actions = array_map('trim', explode(',', $actionsString));

        $this->info("Creating CRUD permissions for '{$resource}'...");

        try {
            $permissions = $this->rbac->createCrudPermissions($resource, $actions);

            $this->info("Created {$permissions->count()} permissions:");

            $permissionData = $permissions->map(fn($perm) => [
                $perm->id,
                $perm->name,
                $perm->display_name,
                $perm->resource,
                $perm->action,
            ])->all();

            $this->table(['ID', 'Name', 'Display Name', 'Resource', 'Action'], $permissionData);

            return 0;
        } catch (\Throwable $e) {
            $this->error("Failed to create permissions: {$e->getMessage()}");
            return 1;
        }
    }
}
