<?php

namespace Sleimanx2\Plastic;

use Illuminate\Support\ServiceProvider;
use Sleimanx2\Plastic\Console\Index\Populate;
use Sleimanx2\Plastic\Console\Index\Recreate;

/**
 * @codeCoverageIgnore
 */
class IndexServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->registerCommands();
    }

    /**
     * Register all needed commands.
     */
    protected function registerCommands()
    {
        $this->registerPopulateCommand();
        $this->registerRecreateCommand();

        $this->commands([
            'command.index.populate',
            'command.index.recreate',
        ]);
    }

    /**
     * Register the Populate command.
     */
    protected function registerPopulateCommand()
    {
        $this->app->singleton('command.index.populate', function () {
            return new Populate();
        });
    }

    /**
     * Register the Recreate command.
     */
    protected function registerRecreateCommand()
    {
        $this->app->singleton('command.index.recreate', function () {
            return new Recreate();
        });
    }
}
