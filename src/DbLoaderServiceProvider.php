<?php

namespace Spatie\DbLoader;

use Illuminate\Support\ServiceProvider;

class DbLoaderServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/db-loader.php' => config_path('db-loader.php'),
            ], 'config');

            /*
            $this->loadViewsFrom(__DIR__.'/../resources/views', 'db-loader');

            $this->publishes([
                __DIR__.'/../resources/views' => base_path('resources/views/vendor/db-loader'),
            ], 'views');
            */
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'db-loader');
    }
}
