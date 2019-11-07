<?php

namespace DigitSoft\Attachments;

use DigitSoft\Attachments\Traits\WithAttachmentsManager;
use DigitSoft\Attachments\Commands\CleanupAttachmentsCommand;
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
        $this->registerTokenManager();
        $this->registerCommands();
    }



    /**
     * Register attachments manager
     */
    protected function registerManager()
    {
        $this->app->singleton('attachments', function ($app) {
            $instance = new AttachmentsManager($app['files'], $app['config']);

            WithAttachmentsManager::$_attachmentsManagerInstance = $instance;

            return $instance;
        });
    }

    /**
     * Register tokens manager
     */
    protected function registerTokenManager()
    {
        $this->app->singleton('attachments.token', function ($app) {
            /** @var \Illuminate\Config\Repository $config */
            /** @var \Illuminate\Foundation\Application $app */
            $config = $app['config'];
            return new TokenManager(
                $app->make('redis'),
                $config->get('attachments.redis_connection'),
                $config->get('attachments.token_expire')
            );
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
        $this->app->singleton('command.attachments.cleanup', function () {
            return new CleanupAttachmentsCommand();
        });

        $this->commands([
            'command.attachments.tables',
            'command.attachments.directories',
            'command.attachments.cleanup',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function provides()
    {
        return [
            'attachments',
            'command.attachments.tables',
            'command.attachments.directories',
            'command.attachments.cleanup',
        ];
    }
}
