<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Una subcarpeta de migraciones por contexto (docs/arquitectura.md, seccion 8.1).
        $this->loadMigrationsFrom(glob(database_path('migrations/*'), GLOB_ONLYDIR) ?: []);
    }
}
