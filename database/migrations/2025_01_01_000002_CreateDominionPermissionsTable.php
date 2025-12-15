<?php

declare(strict_types=1);

use Toporia\Framework\Database\Migration\Migration;

/**
 * Create RBAC permissions table migration.
 */
class CreateDominionPermissionsTable extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(): void
    {
        $tableName = config('dominion.tables.permissions', 'permissions');

        $this->schema->create($tableName, function ($table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('display_name', 200);
            $table->text('description')->nullable();
            $table->string('resource', 50)->nullable();
            $table->string('action', 50)->nullable();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['resource', 'action']);
            $table->index('is_active');
        });
    }

    /**
     * {@inheritdoc}
     */
    public function down(): void
    {
        $tableName = config('dominion.tables.permissions', 'permissions');
        $this->schema->dropIfExists($tableName);
    }
}
