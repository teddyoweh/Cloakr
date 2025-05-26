<?php

namespace Cloakr\Client\Providers;

use Cloakr\Client\Contracts\LogStorageContract;
use Cloakr\Client\Logger\CliLogger;
use Cloakr\Client\Logger\DatabaseLogger;
use Cloakr\Client\Logger\FrontendLogger;
use Cloakr\Client\Logger\Plugins\PluginManager;
use Cloakr\Client\Logger\RequestLogger;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Laminas\Uri\Uri;
use Laminas\Uri\UriFactory;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Http\Browser;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        UriFactory::registerScheme('capacitor', Uri::class);
        UriFactory::registerScheme('chrome-extension', Uri::class);
    }

    public function register()
    {
        $this->loadConfigurationFile();

        $this->setMemoryLimit();

        $this->app->singleton(LoopInterface::class, function () {
            return Loop::get();
        });

        $this->app->singleton(PluginManager::class, function () {
            return new PluginManager;
        });

        $this->app->bind(Browser::class, function ($app) {
            return new Browser($app->make(LoopInterface::class));
        });

        $this->app->singleton(LogStorageContract::class, function ($app) {
            $this->createDatabase();
            return new DatabaseLogger();
        });

        $this->app->singleton(RequestLogger::class, function ($app) {
            return new RequestLogger($app->make(CliLogger::class), $app->make(FrontendLogger::class), $app->make(LogStorageContract::class));
        });
    }

    protected function loadConfigurationFile()
    {
        $builtInConfig = config('cloakr');

        $keyServerVariable = 'EXPOSE_CONFIG_FILE';
        if (array_key_exists($keyServerVariable, $_SERVER) && is_string($_SERVER[$keyServerVariable]) && file_exists($_SERVER[$keyServerVariable])) {
            $localConfig = require $_SERVER[$keyServerVariable];
            config()->set('cloakr', array_merge($builtInConfig, $localConfig));

            return;
        }

        $localConfigFile = getcwd().DIRECTORY_SEPARATOR.'.cloakr.php';

        if (file_exists($localConfigFile)) {
            $localConfig = require $localConfigFile;
            config()->set('cloakr', array_merge($builtInConfig, $localConfig));

            return;
        }

        $configFile = implode(DIRECTORY_SEPARATOR, [
            $_SERVER['HOME'] ?? $_SERVER['USERPROFILE'] ?? __DIR__,
            '.cloakr',
            'config.php',
        ]);

        if (file_exists($configFile)) {
            $globalConfig = require $configFile;
            config()->set('cloakr', array_merge($builtInConfig, $globalConfig));
        }
    }

    protected function setMemoryLimit()
    {
        ini_set('memory_limit', config()->get('cloakr.memory_limit', '128M'));
    }

    protected function createDatabase(): void {
        $databasePath = tempnam(sys_get_temp_dir(), 'cloakr-client-');

        File::put($databasePath, '');

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', $databasePath);
    }
}
