# Toporia Dominion

Enterprise-grade Role-Based Access Control (RBAC) package for Toporia Framework.

## Features

- **Role Management** - Create, update, delete roles with hierarchy support
- **Permission Management** - Resource-based permissions with wildcard support
- **Role Hierarchy** - Parent-child relationships with permission inheritance
- **Direct Permissions** - Assign permissions directly to users (bypass roles)
- **High Performance** - Caching with O(1) permission checks after first load
- **Gate Integration** - Seamless integration with Toporia's authorization Gate
- **Middleware** - Ready-to-use middleware for route protection
- **Console Commands** - Artisan-like commands for RBAC management
- **Super Admin** - Configurable super admin bypass

## Installation

```bash
composer require toporia/dominion
```

## Auto-Discovery

This package uses Toporia's **Package Auto-Discovery** system. After installation:

- **Service Provider** is automatically registered - no manual registration required
- **Configuration** is automatically discovered from `extra.toporia.config` in composer.json
- **Migrations** are automatically included when running `php console migrate`

To rebuild the package manifest manually:

```bash
php console package:discover
```

## Configuration

Publish the configuration file (optional):

```bash
php console vendor:publish --provider="Toporia\Dominion\DominionServiceProvider"
# Or with tag
php console vendor:publish --tag=dominion-config
```

Or manually copy `config/dominion.php` to your application's `config/` directory.

## Database Setup

Run migrations (includes package migrations automatically):

```bash
php console migrate
```

Package migrations are automatically discovered from `packages/dominion/database/migrations/`.
To skip package migrations:

```bash
php console migrate --no-packages
```

To view all migration paths including packages:

```bash
php console migrate:status
```

## Quick Start

### 1. Add HasRoles Trait to User Model

```php
<?php

namespace App\Infrastructure\Persistence\Models;

use Toporia\Framework\Database\ORM\Model;
use Toporia\Dominion\Concerns\HasRoles;

class UserModel extends Model
{
    use HasRoles;

    // ...
}
```

### 2. Create Roles and Permissions

```php
use Toporia\Dominion\Models\Role;
use Toporia\Dominion\Models\Permission;

// Create roles
$admin = Role::create(['name' => 'admin', 'display_name' => 'Administrator']);
$editor = Role::create(['name' => 'editor', 'display_name' => 'Editor']);

// Create permissions
$createUsers = Permission::create(['name' => 'users.create']);
$editUsers = Permission::create(['name' => 'users.update']);
$deleteUsers = Permission::create(['name' => 'users.delete']);

// Or create CRUD permissions at once
Permission::createCrudPermissions('posts'); // Creates posts.create, posts.read, etc.

// Assign permissions to role
$admin->givePermissionTo('users.create', 'users.update', 'users.delete');
$editor->givePermissionTo('posts.create', 'posts.update');
```

### 3. Assign Roles to Users

```php
// Assign roles
$user->assignRole('admin');
$user->assignRole(['admin', 'editor']);

// Assign with expiration
$user->assignRoleWithExpiration('admin', now()->addMonth());

// Remove roles
$user->removeRole('editor');

// Sync roles (replace all)
$user->syncRoles(['editor', 'writer']);
```

### 4. Check Permissions

```php
// Check roles
$user->hasRole('admin');                    // true/false
$user->hasRole(['admin', 'editor']);        // has any
$user->hasAllRoles(['admin', 'editor']);    // has all

// Check permissions
$user->hasPermissionTo('users.create');     // true/false
$user->hasPermissionTo('users.*');          // wildcard check
$user->hasAllPermissions(['a', 'b']);       // has all

// Via Gate
auth()->user()->can('users.create');
```

## Middleware

### Route Protection

```php
// In routes/web.php or routes/api.php

// Require role
$router->get('/admin', [AdminController::class, 'index'])
    ->middleware('role:admin');

// Require any of multiple roles
$router->get('/dashboard', [DashboardController::class, 'index'])
    ->middleware('role:admin,editor');

// Require permission
$router->post('/users', [UserController::class, 'store'])
    ->middleware('permission:users.create');

// Require role OR permission
$router->get('/reports', [ReportController::class, 'index'])
    ->middleware('role_or_permission:admin|reports.view');
```

### Middleware Groups

```php
$router->group(['middleware' => ['role:admin']], function ($router) {
    $router->get('/admin/users', [AdminUserController::class, 'index']);
    $router->get('/admin/settings', [AdminSettingsController::class, 'index']);
});
```

## Console Commands

```bash
# Create a role
php console rbac:create-role admin --display-name="Administrator" --level=100

# Create a permission
php console rbac:create-permission users.create --display-name="Create Users"

# Create CRUD permissions for a resource
php console rbac:make-crud users
php console rbac:make-crud posts --actions=create,read,update,delete,publish

# Assign role to user
php console rbac:assign-role 1 admin
php console rbac:assign-role user@example.com editor --expires="2025-12-31"

# Show all roles and permissions
php console rbac:show
php console rbac:show --roles
php console rbac:show --permissions
php console rbac:show --user=1

# Clear cache
php console rbac:cache-reset
php console rbac:cache-reset --user=1
```

## Role Hierarchy

Roles can have parent-child relationships:

```php
$superAdmin = Role::create(['name' => 'super-admin', 'level' => 100]);
$admin = Role::create(['name' => 'admin', 'level' => 50]);
$editor = Role::create(['name' => 'editor', 'level' => 10]);

// Set hierarchy
$admin->setParent($superAdmin);
$editor->setParent($admin);

// Child roles inherit parent permissions (configurable)
$superAdmin->givePermissionTo('*');  // Super permission
$admin->givePermissionTo('users.*'); // All user permissions
```

## Direct Permissions

Assign permissions directly to users (bypassing roles):

```php
// Give direct permission
$user->givePermissionTo('posts.publish');

// With expiration
$user->givePermissionWithExpiration('posts.publish', now()->addWeek());

// Revoke direct permission
$user->revokePermissionTo('posts.publish');

// Check direct vs role permission
$user->hasDirectPermission('posts.publish');
$user->hasPermissionViaRole('posts.publish');
```

## Super Admin

Configure super admin behavior in `config/dominion.php`:

```php
'super_admin' => [
    'enabled' => true,
    'role' => 'super-admin',
    'permission' => '*',
],
```

Users with the `super-admin` role automatically pass all permission checks.

## Wildcard Permissions

Use wildcards for flexible permission matching:

```php
// Give all user permissions
$role->givePermissionTo('users.*');

// Check with wildcard
$user->hasPermissionTo('users.*');     // Has any users permission
$user->hasPermissionTo('*');           // Super admin check
```

## Caching

The package caches permissions for optimal performance. Configure in `config/dominion.php`:

```php
'cache' => [
    'enabled' => true,
    'driver' => null,    // null = default cache driver
    'ttl' => 86400,      // 24 hours
    'prefix' => 'dominion_',
],
```

Clear cache when needed:

```php
dominion()->clearCache();           // Clear all
dominion()->clearCache($userId);    // Clear for specific user

// Or use the alias
rbac()->clearCache();
```

## Helper Functions

```php
// Get Dominion RBAC manager
dominion();
rbac();  // Alias

// Get role/permission by name
role('admin');
permission('users.create');

// Check current user
has_role('admin');
has_permission('users.create');
has_any_role(['admin', 'editor']);
has_all_permissions(['users.create', 'users.update']);
is_super_admin();
```

## Gate Integration

The package automatically registers with Toporia's Gate:

```php
// These work automatically with RBAC
auth()->user()->can('users.create');
gate()->allows('posts.delete', $post);
authorize('users.update', $user);
```

## API Reference

### Role Model

```php
Role::create(['name' => 'admin']);
Role::findByName('admin');
Role::findOrCreate('admin');

$role->givePermissionTo('permission');
$role->revokePermissionTo('permission');
$role->syncPermissions(['a', 'b']);
$role->hasPermissionTo('permission');
$role->getPermissions();
$role->getAllPermissions();  // Including inherited
$role->setParent($parentRole);
```

### Permission Model

```php
Permission::create(['name' => 'users.create']);
Permission::findByName('users.create');
Permission::findOrCreate('users.create');
Permission::createCrudPermissions('users');
Permission::getResources();
Permission::getActionsForResource('users');

$permission->getRoles();
$permission->matches('users.*');
```

### User Model (with HasRoles trait)

```php
$user->assignRole('admin');
$user->removeRole('admin');
$user->syncRoles(['admin', 'editor']);
$user->hasRole('admin');
$user->hasAllRoles(['admin', 'editor']);
$user->getRoles();
$user->getRoleNames();

$user->givePermissionTo('permission');
$user->revokePermissionTo('permission');
$user->syncPermissions(['a', 'b']);
$user->hasPermissionTo('permission');
$user->hasAllPermissions(['a', 'b']);
$user->getAllPermissions();
$user->getPermissionNames();
$user->hasDirectPermission('permission');
$user->hasPermissionViaRole('permission');
```

## License

MIT License. See [LICENSE](LICENSE) for details.
