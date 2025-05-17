<?php

namespace Cloakr\Client\Logger\Plugins;

use Cloakr\Client\Support\InsertRequestPluginsNodeVisitor;
use Cloakr\Client\Support\RequestPluginsNodeVisitor;
use PhpParser\Lexer\Emulative;
use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\CloningVisitor;
use PhpParser\Parser\Php7;
use PhpParser\PrettyPrinter\Standard;

class PluginManager
{
    protected ?array $defaultPlugins = null;
    protected ?array $customPlugins = null;

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

    public function getPlugins(): array
    {
        return array_merge($this->defaultPlugins, $this->customPlugins);
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
    }

    protected function loadCustomPlugins(): array
    {
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

    public function getCustomPluginDirectory(): string
    {
        return implode(DIRECTORY_SEPARATOR, [
            $_SERVER['HOME'] ?? $_SERVER['USERPROFILE'] ?? __DIR__,
            '.cloakr',
            'plugins'
        ]);
    }

    public function modifyPluginConfiguration(array $pluginsToEnable): void
    {
        $configFile = implode(DIRECTORY_SEPARATOR, [
            $_SERVER['HOME'] ?? $_SERVER['USERPROFILE'],
            '.cloakr',
            'config.php',
        ]);

        if (!file_exists($configFile)) {
            @mkdir(dirname($configFile), 0777, true);
            $updatedConfigFile = $this->writePluginConfig(base_path('config/cloakr.php'), $pluginsToEnable);
        } else {
            $updatedConfigFile = $this->writePluginConfig($configFile, $pluginsToEnable);
        }

        file_put_contents($configFile, $updatedConfigFile);
    }

    protected function writePluginConfig(string $configFile, array $pluginsToEnable)
    {
        $lexer = new Emulative([
            'usedAttributes' => [
                'comments',
                'startLine',
                'endLine',
                'startTokenPos',
                'endTokenPos',
            ],
        ]);
        $parser = new Php7($lexer);

        $oldStmts = $parser->parse(file_get_contents($configFile));
        $oldTokens = $lexer->getTokens();

        $nodeTraverser = new NodeTraverser;
        $nodeTraverser->addVisitor(new CloningVisitor());
        $newStmts = $nodeTraverser->traverse($oldStmts);

        $nodeFinder = new NodeFinder;

        $requestPluginsNode = $nodeFinder->findFirst($newStmts, function (Node $node) {
            return $node instanceof Node\Expr\ArrayItem && $node->key && $node->key->value === 'request_plugins';
        });

        if (is_null($requestPluginsNode)) {
            $nodeTraverser = new NodeTraverser;
            $nodeTraverser->addVisitor(new InsertRequestPluginsNodeVisitor());
            $newStmts = $nodeTraverser->traverse($newStmts);
        }

        $nodeTraverser = new NodeTraverser;
        $nodeTraverser->addVisitor(new RequestPluginsNodeVisitor($pluginsToEnable));

        $newStmts = $nodeTraverser->traverse($newStmts);

        $prettyPrinter = new Standard();

        return $prettyPrinter->printFormatPreserving($newStmts, $oldStmts, $oldTokens);
    }
}
