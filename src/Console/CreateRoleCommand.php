<?php

declare(strict_types=1);

namespace Toporia\Dominion\Console;

use Toporia\Framework\Console\Command;
use Toporia\Dominion\DominionManager;

/**
 * Class CreateRoleCommand
 *
 * Console command to create a new role.
 *
 * Usage: php console rbac:create-role {name} [--display-name=] [--description=] [--level=]
 *
 * @author      Phungtruong7820 <minhphung485@gmail.com>
 * @copyright   Copyright (c) 2025 Toporia Framework
 * @license     MIT
 */
final class CreateRoleCommand extends Command
{
    /**
     * The command signature.
     *
     * @var string
     */
    protected string $signature = 'rbac:create-role {name : The unique name of the role} {--display-name= : Display name} {--description= : Role description} {--level=0 : Hierarchy level}';

    /**
     * The command description.
     *
     * @var string
     */
    protected string $description = 'Create a new role';

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
        $level = (int) $this->option('level');

        // Check if role already exists
        if ($this->rbac->findRole($name) !== null) {
            $this->error("Role '{$name}' already exists.");
            return 1;
        }

        try {
            $roleClass = $this->rbac->getRoleClass();

            $role = $roleClass::create([
                'name' => $name,
                'display_name' => $displayName,
                'description' => $description,
                'level' => $level,
            ]);

            $this->info("Role '{$role->name}' created successfully.");
            $this->table(
                ['ID', 'Name', 'Display Name', 'Level'],
                [[$role->id, $role->name, $role->display_name, $role->level]]
            );

            return 0;
        } catch (\Throwable $e) {
            $this->error("Failed to create role: {$e->getMessage()}");
            return 1;
        }
    }
}
