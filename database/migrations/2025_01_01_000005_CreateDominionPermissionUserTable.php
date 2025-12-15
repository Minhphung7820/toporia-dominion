<?php

declare(strict_types=1);

use Toporia\Framework\Database\Migration\Migration;

/**
 * Create RBAC permission_user pivot table migration.
 * Direct user permissions bypass roles for specific permission assignments.
 */
class CreateDominionPermissionUserTable extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(): void
    {
        $tableName = config('dominion.tables.permission_user', 'permission_user');
        $permissionsTable = config('dominion.tables.permissions', 'permissions');

        $this->schema->create($tableName, function ($table) use ($permissionsTable) {
            $table->id();
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['permission_id', 'user_id']);
            $table->index(['user_id', 'is_active']);
            $table->index('expires_at');

            $table->foreign('permission_id')->references($permissionsTable, 'id')->onDelete('cascade');
        });
    }

    /**
     * {@inheritdoc}
     */
    public function down(): void
    {
        $tableName = config('dominion.tables.permission_user', 'permission_user');
        $this->schema->dropIfExists($tableName);
    }
}
