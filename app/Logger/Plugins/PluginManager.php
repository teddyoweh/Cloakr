<?php

namespace Cloakr\Client\Logger\Plugins;

class PluginManager
{
    protected ?array $defaultPlugins = null;
    protected ?array $customPlugins = null;
    protected bool $configValidated = false;

    public function __construct()
    {
        $this->loadDefaultPlugins();
        $this->loadCustomPlugins();
        $this->ensureValidPluginConfig();
    }

    public function getDefaultPlugins(): array
    {
        return $this->defaultPlugins;
    }

    public function getCustomPlugins(): array
    {
        return $this->customPlugins;
    }

    public function loadPluginData(): ?PluginData // TODO
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
        if ($this->configValidated) {
            return;
        }

        foreach (config('cloakr.request_plugins') as $pluginClass) {
            // Remove invalid plugins from the configuration
            if (!class_exists($pluginClass) || !is_subclass_of($pluginClass, BasePlugin::class)) {
                $configPlugins = config('cloakr.request_plugins');

                if (in_array($pluginClass, $configPlugins)) {
                    $configPlugins = array_diff($configPlugins, [$pluginClass]);
                    config(['cloakr.request_plugins' => $configPlugins]);
                }
            }
        }

        $this->configValidated = true;
    }

    protected function loadCustomPlugins(): array
    {
        if ($this->customPlugins !== null) {
            return $this->customPlugins;
        }

        $pluginDirectory = $this->getCustomPluginDirectory();

        if (!is_dir($pluginDirectory)) {
            return [];
        }

        $this->customPlugins = [];

        foreach (scandir($pluginDirectory) as $file) {
            if (str($file)->startsWith('.')) {
                continue;
            }

            require_once $pluginDirectory . DIRECTORY_SEPARATOR . $file;

            $pluginClass = 'Cloakr\\Client\\Logger\\Plugins\\' . pathinfo($file, PATHINFO_FILENAME);

            $this->customPlugins[] = $pluginClass;
        }

       return $this->customPlugins;
    }

    protected function loadDefaultPlugins(): array
    {
        if ($this->defaultPlugins !== null) {
            return $this->defaultPlugins;
        }

        $defaultPluginDirectory = scandir($this->getDefaultPluginDirectory());
        $this->defaultPlugins = [];

        foreach ($defaultPluginDirectory as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            require_once $this->getDefaultPluginDirectory() . DIRECTORY_SEPARATOR . $file;

            $pluginClass = 'Cloakr\\Client\\Logger\\Plugins\\' . pathinfo($file, PATHINFO_FILENAME);

            if (!class_exists($pluginClass) || !is_subclass_of($pluginClass, BasePlugin::class)) {
                continue;
            }

            $this->defaultPlugins[] = $pluginClass;
        }

        return $this->defaultPlugins;
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
