<?php

declare(strict_types=1);

namespace Toporia\Dominion\Console;

use Toporia\Framework\Console\Command;
use Toporia\Dominion\DominionManager;

/**
 * Class CreatePermissionCommand
 *
 * Console command to create a new permission.
 *
 * Usage: php console rbac:create-permission {name} [--display-name=] [--description=]
 *
 * @author      Phungtruong7820 <minhphung485@gmail.com>
 * @copyright   Copyright (c) 2025 Toporia Framework
 * @license     MIT
 */
final class CreatePermissionCommand extends Command
{
    /**
     * The command signature.
     *
     * @var string
     */
    protected string $signature = 'rbac:create-permission {name : The unique name (e.g., users.create)} {--display-name= : Display name} {--description= : Permission description}';

    /**
     * The command description.
     *
     * @var string
     */
    protected string $description = 'Create a new permission';

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
        $name = $this->argument('name');
        $displayName = $this->option('display-name');
        $description = $this->option('description');

        // Check if permission already exists
        if ($this->rbac->findPermission($name) !== null) {
            $this->error("Permission '{$name}' already exists.");
            return 1;
        }

        try {
            $permissionClass = $this->rbac->getPermissionClass();

            $permission = $permissionClass::create([
                'name' => $name,
                'display_name' => $displayName,
                'description' => $description,
            ]);

            $this->info("Permission '{$permission->name}' created successfully.");
            $this->table(
                ['ID', 'Name', 'Display Name', 'Resource', 'Action'],
                [[$permission->id, $permission->name, $permission->display_name, $permission->resource, $permission->action]]
            );

            return 0;
        } catch (\Throwable $e) {
            $this->error("Failed to create permission: {$e->getMessage()}");
            return 1;
        }
    }
}
