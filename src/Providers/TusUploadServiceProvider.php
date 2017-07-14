<?php

namespace Avvertix\TusUpload\Providers;

use Illuminate\Support\ServiceProvider;

use Avvertix\TusUpload\Console\Commands\TusServerStartCommand;
use Avvertix\TusUpload\Console\Commands\TusHookProcessingCommand;

use Avvertix\TusUpload\Contracts\AuthenticationResolver as AuthenticationResolverContract;
use Avvertix\TusUpload\Auth\AuthenticationResolver;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Support\Facades\Auth;

class TusUploadServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/../../routes/tusupload.php');

        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        $this->publishes([
            __DIR__.'/../../config/tusupload.php' => config_path('tusupload.php'),
        ], 'tusupload-config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                TusServerStartCommand::class,
                TusHookProcessingCommand::class,
            ]);
        }
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/tusupload.php', 'tusupload'
        );

        $this->app->bind(AuthenticationResolverContract::class, AuthenticationResolver::class);
        $this->app->singleton(AuthenticationResolver::class, function($app){
            return new AuthenticationResolver(
                $app->make(Gate::class), 
                Auth::createUserProvider(config('auth.guards.api.provider')));
        });
    }
}
