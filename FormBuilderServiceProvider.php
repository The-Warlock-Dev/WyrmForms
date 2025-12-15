<?php

namespace YourUsername\FormBuilder;

use Illuminate\Support\ServiceProvider;

class FormBuilderServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        // Load routes
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Load views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'form-builder');

        // Publish configuration
        $this->publishes([
            __DIR__.'/../config/form-builder.php' => config_path('form-builder.php'),
        ], 'form-builder-config');

        // Publish migrations
        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'form-builder-migrations');

        // Publish views
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/form-builder'),
        ], 'form-builder-views');

        // Publish public assets (if any)
        $this->publishes([
            __DIR__.'/../public' => public_path('vendor/form-builder'),
        ], 'form-builder-assets');

        // Publish all together
        $this->publishes([
            __DIR__.'/../config/form-builder.php' => config_path('form-builder.php'),
            __DIR__.'/../resources/views' => resource_path('views/vendor/form-builder'),
        ], 'form-builder');
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__.'/../config/form-builder.php',
            'form-builder'
        );
    }
}
