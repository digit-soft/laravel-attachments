<?php

namespace DigitSoft\Attachments;

use DigitSoft\Attachments\Commands\CreateDirectoriesCommand;
use DigitSoft\Attachments\Commands\CreateMigrationCommand;
use Illuminate\Support\ServiceProvider;

/**
 * Class AttachmentsServiceProvider
 * @package DigitSoft\Attachments
 */
class AttachmentsServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap the application events and publish config.
     *
     * @return void
     */
    public function boot()
    {
        $configPath = __DIR__ . '/../config/attachments.php';
        if (function_exists('config_path')) {
            $publishPath = config_path('attachments.php');
        } else {
            $publishPath = base_path('config/attachments.php');
        }
        $this->publishes([$configPath => $publishPath], 'config');
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        $configPath = __DIR__ . '/../config/attachments.php';
        $this->mergeConfigFrom($configPath, 'attachments');

        $this->registerManager();
        $this->registerCommands();
    }



    /**
     * Register attachments manager
     */
    protected function registerManager()
    {
        $this->app->singleton('attachments', function ($app) {
            return new AttachmentsManager($app['files'], $app['config']);
        });
    }

    /**
     * Register console commands
     */
    protected function registerCommands()
    {
        $this->app->singleton('command.attachments.tables', function ($app) {
            return new CreateMigrationCommand($app['files']);
        });
        $this->app->singleton('command.attachments.directories', function ($app) {
            return new CreateDirectoriesCommand($app['files'], $app['config']);
        });

        $this->commands([
            'command.attachments.tables',
            'command.attachments.directories',
        ]);
    }

    public function provides()
    {
        return [
            'attachments',
            'command.attachments.tables',
            'command.attachments.directories',
        ];
    }
}