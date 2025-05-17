<?php

namespace App\Logger\Concerns;

use App\Logger\Plugins\BasePlugin;
use App\Logger\Plugins\PluginData;

trait PluginAware
{

    protected function loadPluginData(): ?PluginData
    {
        $this->loadCustomPlugins();

        foreach (config('cloakr.request_plugins') as $pluginClass) {
            try {
                $plugin = $pluginClass::make($this);

                if ($plugin->matchesRequest()) {
                    return $plugin->getPluginData();
                }
            } catch (\Exception $e) {}
        }

        return null;
    }

    protected function loadCustomPlugins(): void
    {
        $driverDirectory = implode(DIRECTORY_SEPARATOR, [
            $_SERVER['HOME'] ?? $_SERVER['USERPROFILE'] ?? __DIR__,
            '.cloakr',
            'plugins',
        ]);

        if (!is_dir($driverDirectory)) {
            return;
        }

        foreach (scandir($driverDirectory) as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            require_once $driverDirectory . DIRECTORY_SEPARATOR . $file;

            $pluginClass = 'App\\Logger\\Plugins\\' . pathinfo($file, PATHINFO_FILENAME);

            if (!class_exists($pluginClass) || !is_subclass_of($pluginClass, BasePlugin::class)) {
                $configPlugins = config('cloakr.request_plugins');

                if (in_array($pluginClass, $configPlugins)) {
                    $configPlugins = array_diff($configPlugins, [$pluginClass]);
                    config(['cloakr.request_plugins' => $configPlugins]);
                }
            }

        }
    }
}
