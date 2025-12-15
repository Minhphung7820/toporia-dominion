<?php

declare(strict_types=1);

namespace Toporia\Dominion;

use Toporia\Framework\Foundation\ServiceProvider;
use Toporia\Framework\Container\Contracts\ContainerInterface;
use Toporia\Framework\Cache\Contracts\CacheInterface;
use Toporia\Framework\Auth\Contracts\GateContract;
use Toporia\Dominion\Cache\CacheKeyGenerator;
use Toporia\Dominion\Cache\PermissionCache;
use Toporia\Dominion\Contracts\DominionManagerInterface;
use Toporia\Dominion\Gate\DominionGateExtension;
use Toporia\Dominion\Middleware\RoleMiddleware;
use Toporia\Dominion\Middleware\PermissionMiddleware;
use Toporia\Dominion\Middleware\RoleOrPermissionMiddleware;

/**
 * Class DominionServiceProvider
 *
 * Service provider for RBAC package registration and bootstrapping.
 *
 * @author      Phungtruong7820 <minhphung485@gmail.com>
 * @copyright   Copyright (c) 2025 Toporia Framework
 * @license     MIT
 */
final class DominionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @param ContainerInterface $container
     * @return void
     */
    public function register(ContainerInterface $container): void
    {
        // Register cache key generator
        $container->singleton(CacheKeyGenerator::class, function () {
            return new CacheKeyGenerator(config('dominion.cache.prefix', 'dominion_'));
        });

        // Register permission cache
        $container->singleton(PermissionCache::class, function (ContainerInterface $c) {
            $cache = $c->has(CacheInterface::class)
                ? $c->get(CacheInterface::class)
                : $c->get('cache');

            return new PermissionCache(
                $cache,
                $c->get(CacheKeyGenerator::class)
            );
        });

        // Register registrar
        $container->singleton(DominionRegistrar::class, function (ContainerInterface $c) {
            return new DominionRegistrar(
                $c->get(PermissionCache::class)
            );
        });

        // Register manager
        $container->singleton(DominionManager::class, function (ContainerInterface $c) {
            return new DominionManager(
                $c->get(DominionRegistrar::class),
                config('dominion', [])
            );
        });

        // Bind interface to implementation
        $container->bind(DominionManagerInterface::class, DominionManager::class);

        // Register aliases
        $container->alias('dominion', DominionManager::class);
        $container->alias('rbac', DominionManager::class);

        // Register middleware
        $container->bind(RoleMiddleware::class, function (ContainerInterface $c) {
            return new RoleMiddleware($c->get(DominionManager::class));
        });

        $container->bind(PermissionMiddleware::class, function (ContainerInterface $c) {
            return new PermissionMiddleware($c->get(DominionManager::class));
        });

        $container->bind(RoleOrPermissionMiddleware::class, function (ContainerInterface $c) {
            return new RoleOrPermissionMiddleware($c->get(DominionManager::class));
        });

        // Register gate extension
        $container->singleton(DominionGateExtension::class, function (ContainerInterface $c) {
            return new DominionGateExtension($c->get(DominionManager::class));
        });
    }

    /**
     * Bootstrap services.
     *
     * @param ContainerInterface $container
     * @return void
     */
    public function boot(ContainerInterface $container): void
    {
        // Register middleware aliases
        $this->registerMiddlewareAliases($container);

        // Register Gate integration
        if (config('dominion.register_gate', true)) {
            $this->registerGateIntegration($container);
        }

        // Register console commands
        if (app()->runningInConsole()) {
            $this->registerCommands($container);
        }
    }

    /**
     * Register middleware aliases.
     *
     * @param ContainerInterface $container
     * @return void
     */
    protected function registerMiddlewareAliases(ContainerInterface $container): void
    {
        if (!$container->has('middleware.aliases')) {
            return;
        }

        $aliases = $container->get('middleware.aliases');

        if (!is_array($aliases)) {
            return;
        }

        $middlewareConfig = config('dominion.middleware', []);

        $aliases['role'] = $middlewareConfig['role'] ?? RoleMiddleware::class;
        $aliases['permission'] = $middlewareConfig['permission'] ?? PermissionMiddleware::class;
        $aliases['role_or_permission'] = $middlewareConfig['role_or_permission'] ?? RoleOrPermissionMiddleware::class;

        $container->instance('middleware.aliases', $aliases);
    }

    /**
     * Register Gate integration.
     *
     * @param ContainerInterface $container
     * @return void
     */
    protected function registerGateIntegration(ContainerInterface $container): void
    {
        if (!$container->has(GateContract::class)) {
            return;
        }

        try {
            $gate = $container->get(GateContract::class);
            $extension = $container->get(DominionGateExtension::class);
            $extension->register($gate);
        } catch (\Throwable $e) {
            // Gate not available, skip integration
        }
    }

    /**
     * Register console commands.
     *
     * @param ContainerInterface $container
     * @return void
     */
    protected function registerCommands(ContainerInterface $container): void
    {
        // Commands will be registered via Kernel or auto-discovery
        // This is a placeholder for explicit command registration if needed
    }
}
