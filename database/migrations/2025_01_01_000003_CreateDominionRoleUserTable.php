<?php

declare(strict_types=1);

use Toporia\Framework\Database\Migration\Migration;

/**
 * Create RBAC role_user pivot table migration.
 */
class CreateDominionRoleUserTable extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(): void
    {
        $tableName = config('dominion.tables.role_user', 'role_user');
        $rolesTable = config('dominion.tables.roles', 'roles');

        $this->schema->create($tableName, function ($table) use ($rolesTable) {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['role_id', 'user_id']);
            $table->index(['user_id', 'is_active']);
            $table->index('expires_at');

            $table->foreign('role_id')->references($rolesTable, 'id')->onDelete('cascade');
        });
    }

    /**
     * {@inheritdoc}
     */
    public function down(): void
    {
        $tableName = config('dominion.tables.role_user', 'role_user');
        $this->schema->dropIfExists($tableName);
    }
}
