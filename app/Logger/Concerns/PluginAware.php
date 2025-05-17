<?php

namespace Cloakr\Client\Logger\Concerns;

use Cloakr\Client\Logger\Plugins\BasePlugin;
use Cloakr\Client\Logger\Plugins\PluginData;

trait PluginAware
{

    protected function loadPluginData(): ?PluginData
    {
        $this->loadCustomPlugins();
        $this->ensureValidPluginConfig();

        foreach (config('cloakr.request_plugins') as $pluginClass) {
            try {
                $plugin = $pluginClass::make($this);

                if ($plugin->matchesRequest()) {
                    return $plugin->getPluginData();
                }
            } catch (\Exception $e) {
            }
        }

        return null;
    }

    protected function ensureValidPluginConfig(): void
    {

        foreach (config('cloakr.request_plugins') as $pluginClass) {
            if (!class_exists($pluginClass) || !is_subclass_of($pluginClass, BasePlugin::class)) {
                $configPlugins = config('cloakr.request_plugins');

                if (in_array($pluginClass, $configPlugins)) {
                    $configPlugins = array_diff($configPlugins, [$pluginClass]);
                    config(['cloakr.request_plugins' => $configPlugins]);
                }
            }
        }
    }

    protected function loadCustomPlugins(): array
    {
        $customPlugins = [];

        $pluginDirectory = $this->getCustomPluginDirectory();

        if (!is_dir($pluginDirectory)) {
            return [];
        }

        foreach (scandir($pluginDirectory) as $file) {
            if (str($file)->startsWith('.')) {
                continue;
            }

            require_once $pluginDirectory . DIRECTORY_SEPARATOR . $file;

            $pluginClass = 'Cloakr\\Client\\Logger\\Plugins\\' . pathinfo($file, PATHINFO_FILENAME);

            $customPlugins[] = $pluginClass;

        }

        return $customPlugins;
    }

    protected function loadDefaultPlugins(): array
    {
        $defaultPluginDirectory = scandir($this->getDefaultPluginDirectory());
        $defaultPlugins = [];


        foreach ($defaultPluginDirectory as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            require_once $this->getDefaultPluginDirectory() . DIRECTORY_SEPARATOR . $file;

            $pluginClass = 'Cloakr\\Client\\Logger\\Plugins\\' . pathinfo($file, PATHINFO_FILENAME);

            if (!class_exists($pluginClass) || !is_subclass_of($pluginClass, BasePlugin::class)) {
                continue;
            }

            $defaultPlugins[] = $pluginClass;
        }

        return $defaultPlugins;
    }

    protected function getDefaultPluginDirectory(): string
    {
        return __DIR__ . '/../Plugins';
    }

    protected function getCustomPluginDirectory(): string
    {
        return implode(DIRECTORY_SEPARATOR, [
            $_SERVER['HOME'] ?? $_SERVER['USERPROFILE'] ?? __DIR__,
            '.cloakr',
            'plugins'
        ]);
    }
}
