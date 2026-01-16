<?php

namespace Modules\SpecialProductGeneral\Providers;

use Illuminate\Support\ServiceProvider;

class SpecialProductGeneralServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'SpecialProductGeneral';
    protected string $moduleNameLower = 'specialproductgeneral';

    public function boot(): void
    {
        $this->registerConfig();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }

    protected function registerConfig(): void
    {
        $this->publishes([
            module_path($this->moduleName, 'Config/config.php') => config_path($this->moduleNameLower . '.php'),
        ], 'config');

        $this->mergeConfigFrom(
            module_path($this->moduleName, 'Config/config.php'),
            $this->moduleNameLower
        );
    }
}