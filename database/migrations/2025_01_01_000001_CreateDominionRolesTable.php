<?php

declare(strict_types=1);

use Toporia\Framework\Database\Migration\Migration;

/**
 * Create RBAC roles table migration.
 */
class CreateDominionRolesTable extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(): void
    {
        $tableName = config('dominion.tables.roles', 'roles');

        $this->schema->create($tableName, function ($table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('display_name', 200);
            $table->text('description')->nullable();
            $table->integer('level')->default(0);
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'level']);
            $table->index('parent_id');
        });
    }

    /**
     * {@inheritdoc}
     */
    public function down(): void
    {
        $tableName = config('dominion.tables.roles', 'roles');
        $this->schema->dropIfExists($tableName);
    }
}
