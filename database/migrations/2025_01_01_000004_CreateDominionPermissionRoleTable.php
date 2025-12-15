<?php

declare(strict_types=1);

use Toporia\Framework\Database\Migration\Migration;

/**
 * Create RBAC permission_role pivot table migration.
 */
class CreateDominionPermissionRoleTable extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(): void
    {
        $tableName = config('dominion.tables.permission_role', 'permission_role');
        $rolesTable = config('dominion.tables.roles', 'roles');
        $permissionsTable = config('dominion.tables.permissions', 'permissions');

        $this->schema->create($tableName, function ($table) use ($rolesTable, $permissionsTable) {
            $table->id();
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->timestamps();

            $table->unique(['permission_id', 'role_id']);

            $table->foreign('permission_id')->references($permissionsTable, 'id')->onDelete('cascade');
            $table->foreign('role_id')->references($rolesTable, 'id')->onDelete('cascade');
        });
    }

    /**
     * {@inheritdoc}
     */
    public function down(): void
    {
        $tableName = config('dominion.tables.permission_role', 'permission_role');
        $this->schema->dropIfExists($tableName);
    }
}
