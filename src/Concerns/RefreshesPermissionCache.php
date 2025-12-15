<?php

declare(strict_types=1);

namespace Toporia\Dominion\Concerns;

/**
 * Trait RefreshesPermissionCache
 *
 * Automatically clears permission cache when models are modified.
 * Add this trait to Role and Permission models.
 *
 * @author      Phungtruong7820 <minhphung485@gmail.com>
 * @copyright   Copyright (c) 2025 Toporia Framework
 * @license     MIT
 */
trait RefreshesPermissionCache
{
    /**
     * Boot the RefreshesPermissionCache trait.
     *
     * @return void
     */
    public static function bootRefreshesPermissionCache(): void
    {
        static::created(fn($model) => $model->refreshPermissionCache());
        static::updated(fn($model) => $model->refreshPermissionCache());
        static::deleted(fn($model) => $model->refreshPermissionCache());

        if (method_exists(static::class, 'restored')) {
            static::restored(fn($model) => $model->refreshPermissionCache());
        }
    }

    /**
     * Refresh the permission cache.
     *
     * @return void
     */
    public function refreshPermissionCache(): void
    {
        if (function_exists('rbac')) {
            rbac()->clearCache();
        }
    }
}
