<?php

declare(strict_types=1);

namespace Toporia\Dominion\Console;

use Toporia\Framework\Console\Command;
use Toporia\Dominion\DominionManager;

/**
 * Class ShowPermissionsCommand
 *
 * Console command to show roles and permissions for a user or list all.
 *
 * Usage:
 * - php console rbac:show              # List all roles and permissions
 * - php console rbac:show --user=1     # Show user's roles and permissions
 * - php console rbac:show --roles      # List all roles only
 * - php console rbac:show --permissions # List all permissions only
 *
 * @author      Phungtruong7820 <minhphung485@gmail.com>
 * @copyright   Copyright (c) 2025 Toporia Framework
 * @license     MIT
 */
final class ShowPermissionsCommand extends Command
{
    /**
     * The command signature.
     *
     * @var string
     */
    protected string $signature = 'rbac:show {--user= : Show for specific user ID} {--roles : List all roles} {--permissions : List all permissions}';

    /**
     * The command description.
     *
     * @var string
     */
    protected string $description = 'Show roles and permissions';

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
        $showRolesOnly = $this->option('roles');
        $showPermissionsOnly = $this->option('permissions');

        if ($userId !== null) {
            return $this->showUserPermissions((int) $userId);
        }

        if ($showRolesOnly) {
            return $this->listAllRoles();
        }

        if ($showPermissionsOnly) {
            return $this->listAllPermissions();
        }

        // Show both by default
        $this->listAllRoles();
        $this->line('');
        $this->listAllPermissions();

        return 0;
    }

    /**
     * Show permissions for a specific user.
     *
     * @param int $userId
     * @return int
     */
    private function showUserPermissions(int $userId): int
    {
        $user = $this->findUser($userId);

        if ($user === null) {
            $this->error("User #{$userId} not found.");
            return 1;
        }

        if (!method_exists($user, 'getRoles')) {
            $this->error('User model does not support roles. Add the HasRoles trait.');
            return 1;
        }

        $this->info("User: #{$user->id} ({$user->email ?? $user->name ?? 'N/A'})");
        $this->line('');

        // Show roles
        $this->info('Roles:');
        $roles = $user->getRoles();

        if ($roles->isEmpty()) {
            $this->line('  No roles assigned.');
        } else {
            $roleData = $roles->map(fn($role) => [
                $role->id,
                $role->name,
                $role->display_name,
                $role->level,
            ])->all();

            $this->table(['ID', 'Name', 'Display Name', 'Level'], $roleData);
        }

        $this->line('');

        // Show permissions
        $this->info('Permissions:');
        $permissions = $user->getAllPermissions();

        if ($permissions->isEmpty()) {
            $this->line('  No permissions.');
        } else {
            $permissionData = $permissions->map(fn($perm) => [
                $perm->id,
                $perm->name,
                $perm->resource,
                $perm->action,
            ])->all();

            $this->table(['ID', 'Name', 'Resource', 'Action'], $permissionData);
        }

        return 0;
    }

    /**
     * List all roles.
     *
     * @return int
     */
    private function listAllRoles(): int
    {
        $this->info('All Roles:');

        $roles = $this->rbac->getAllRoles();

        if ($roles->isEmpty()) {
            $this->line('  No roles found.');
            return 0;
        }

        $roleData = $roles->map(fn($role) => [
            $role->id,
            $role->name,
            $role->display_name,
            $role->level,
            $role->is_system ? 'Yes' : 'No',
            $role->is_active ? 'Yes' : 'No',
        ])->all();

        $this->table(['ID', 'Name', 'Display Name', 'Level', 'System', 'Active'], $roleData);

        return 0;
    }

    /**
     * List all permissions.
     *
     * @return int
     */
    private function listAllPermissions(): int
    {
        $this->info('All Permissions:');

        $permissions = $this->rbac->getAllPermissions();

        if ($permissions->isEmpty()) {
            $this->line('  No permissions found.');
            return 0;
        }

        $permissionData = $permissions->map(fn($perm) => [
            $perm->id,
            $perm->name,
            $perm->resource ?? '-',
            $perm->action ?? '-',
            $perm->is_system ? 'Yes' : 'No',
            $perm->is_active ? 'Yes' : 'No',
        ])->all();

        $this->table(['ID', 'Name', 'Resource', 'Action', 'System', 'Active'], $permissionData);

        return 0;
    }

    /**
     * Find a user by ID.
     *
     * @param int $userId
     * @return mixed
     */
    private function findUser(int $userId): mixed
    {
        $userClass = config('dominion.user_model')
            ?? config('auth.providers.users.model')
            ?? 'App\\Infrastructure\\Persistence\\Models\\UserModel';

        return $userClass::find($userId);
    }
}
