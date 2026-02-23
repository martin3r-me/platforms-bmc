<?php

namespace Platform\Bmc;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Platform\Core\PlatformCore;
use Platform\Core\Routing\ModuleRouter;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class BmcServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Step 1: Load config
        $this->mergeConfigFrom(__DIR__ . '/../config/bmc.php', 'bmc');
        $this->mergeConfigFrom(__DIR__ . '/../config/bmc-templates.php', 'bmc-templates');

        // Step 2: Register module
        if (
            config()->has('bmc.routing') &&
            config()->has('bmc.navigation') &&
            Schema::hasTable('modules')
        ) {
            PlatformCore::registerModule([
                'key' => 'bmc',
                'title' => 'BMC',
                'routing' => config('bmc.routing'),
                'guard' => config('bmc.guard'),
                'navigation' => config('bmc.navigation'),
                'sidebar' => config('bmc.sidebar'),
            ]);
        }

        // Step 3: Routes (if module registered)
        if (PlatformCore::getModule('bmc')) {
            ModuleRouter::group('bmc', function () {
                $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
            });
        }

        // Step 4: Migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Step 5: Publish config
        $this->publishes([
            __DIR__ . '/../config/bmc.php' => config_path('bmc.php'),
            __DIR__ . '/../config/bmc-templates.php' => config_path('bmc-templates.php'),
        ], 'config');

        // Step 6: Views & Livewire
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'bmc');
        $this->registerLivewireComponents();

        // Step 7: Tools
        $this->registerTools();
    }

    protected function registerTools(): void
    {
        try {
            $registry = resolve(\Platform\Core\Tools\ToolRegistry::class);

            // Overview
            $registry->register(new \Platform\Bmc\Tools\BmcOverviewTool());

            // Canvas CRUD
            $registry->register(new \Platform\Bmc\Tools\ListCanvasesTool());
            $registry->register(new \Platform\Bmc\Tools\GetCanvasTool());
            $registry->register(new \Platform\Bmc\Tools\CreateCanvasTool());
            $registry->register(new \Platform\Bmc\Tools\UpdateCanvasTool());
            $registry->register(new \Platform\Bmc\Tools\DeleteCanvasTool());

            // Entry CRUD
            $registry->register(new \Platform\Bmc\Tools\ListEntriesTool());
            $registry->register(new \Platform\Bmc\Tools\CreateEntryTool());
            $registry->register(new \Platform\Bmc\Tools\UpdateEntryTool());
            $registry->register(new \Platform\Bmc\Tools\DeleteEntryTool());

            // Entry Bulk/Reorder
            $registry->register(new \Platform\Bmc\Tools\BulkCreateEntriesTool());
            $registry->register(new \Platform\Bmc\Tools\ReorderEntriesTool());

            // Snapshots
            $registry->register(new \Platform\Bmc\Tools\CreateSnapshotTool());
            $registry->register(new \Platform\Bmc\Tools\ListSnapshotsTool());
            $registry->register(new \Platform\Bmc\Tools\GetSnapshotTool());
            $registry->register(new \Platform\Bmc\Tools\CompareSnapshotsTool());

            // Utilities
            $registry->register(new \Platform\Bmc\Tools\ExportCanvasTool());
            $registry->register(new \Platform\Bmc\Tools\CalculateTool());
        } catch (\Throwable $e) {
            \Log::warning('BMC: Tool-Registrierung fehlgeschlagen', ['error' => $e->getMessage()]);
        }
    }

    protected function registerLivewireComponents(): void
    {
        $basePath = __DIR__ . '/Livewire';
        $baseNamespace = 'Platform\\Bmc\\Livewire';
        $prefix = 'bmc';

        if (!is_dir($basePath)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($basePath)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = str_replace($basePath . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $classPath = str_replace(['/', '.php'], ['\\', ''], $relativePath);
            $class = $baseNamespace . '\\' . $classPath;

            if (!class_exists($class)) {
                continue;
            }

            $aliasPath = str_replace(['\\', '/'], '.', Str::kebab(str_replace('.php', '', $relativePath)));
            $alias = $prefix . '.' . $aliasPath;

            Livewire::component($alias, $class);
        }
    }
}
