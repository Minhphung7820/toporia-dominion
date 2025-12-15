<?php

declare(strict_types=1);

namespace Toporia\Dominion\Console;

use Toporia\Framework\Console\Command;
use Toporia\Dominion\DominionManager;

/**
 * Class AssignRoleCommand
 *
 * Console command to assign a role to a user.
 *
 * Usage: php console rbac:assign-role {user} {role}
 *
 * @author      Phungtruong7820 <minhphung485@gmail.com>
 * @copyright   Copyright (c) 2025 Toporia Framework
 * @license     MIT
 */
final class AssignRoleCommand extends Command
{
    /**
     * The command signature.
     *
     * @var string
     */
    protected string $signature = 'rbac:assign-role {user : User ID or email} {role : Role name} {--expires= : Expiration date (Y-m-d H:i:s)}';

    /**
     * The command description.
     *
     * @var string
     */
    protected string $description = 'Assign a role to a user';

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
        $userIdentifier = $this->argument('user');
        $roleName = $this->argument('role');
        $expires = $this->option('expires');

        // Find the role
        $role = $this->rbac->findRole($roleName);

        if ($role === null) {
            $this->error("Role '{$roleName}' not found.");
            return 1;
        }

        // Find the user
        $user = $this->findUser($userIdentifier);

        if ($user === null) {
            $this->error("User '{$userIdentifier}' not found.");
            return 1;
        }

        // Check if user model supports roles
        if (!method_exists($user, 'assignRole')) {
            $this->error('User model does not support roles. Add the HasRoles trait.');
            return 1;
        }

        try {
            if ($expires) {
                $user->assignRoleWithExpiration($role, $expires);
                $this->info("Role '{$roleName}' assigned to user #{$user->id} until {$expires}.");
            } else {
                $user->assignRole($role);
                $this->info("Role '{$roleName}' assigned to user #{$user->id}.");
            }

            return 0;
        } catch (\Throwable $e) {
            $this->error("Failed to assign role: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * Find a user by ID or email.
     *
     * @param string $identifier
     * @return mixed
     */
    private function findUser(string $identifier): mixed
    {
        $userClass = config('dominion.user_model')
            ?? config('auth.providers.users.model')
            ?? 'App\\Infrastructure\\Persistence\\Models\\UserModel';

        // Try to find by ID first
        if (is_numeric($identifier)) {
            return $userClass::find((int) $identifier);
        }

        // Try to find by email
        return $userClass::where('email', $identifier)->first();
    }
}
