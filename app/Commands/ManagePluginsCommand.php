<?php

namespace App\Commands;

use App\Client\Support\InsertRequestPluginsNodeVisitor;
use App\Client\Support\RequestPluginsNodeVisitor;
use App\Commands\Concerns\RendersBanner;
use App\Commands\Concerns\RendersOutput;
use App\Logger\Concerns\PluginAware;
use LaravelZero\Framework\Commands\Command;
use PhpParser\Lexer\Emulative;
use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\CloningVisitor;
use PhpParser\Parser\Php7;
use PhpParser\PrettyPrinter\Standard;
use function Laravel\Prompts\multiselect;
use function Termwind\render;

class ManagePluginsCommand extends Command
{
    use RendersBanner, RendersOutput;
    use PluginAware;

    protected $signature = 'plugins:manage {--add=}';

    protected $description = 'Activate and deactivate request plugins.';


    public function handle()
    {

        $defaultPlugins = $this->loadDefaultPlugins();

        $customPlugins = $this->loadCustomPlugins();
        $this->ensureValidPluginConfig();

        if($this->option('add')) {
            $this->addPlugin($this->option('add'));
            return;
        }


        $this->renderBanner();

        render('<div class="ml-3">Explanation text about request plugins goes here.</div>'); // TODO:


        // Build key-based list for easier Windows support
        $activePlugins = collect(config('cloakr.request_plugins'))
            ->map(function ($pluginClass) {
                return str($pluginClass)->afterLast('\\')->__toString();
            })
            ->toArray();


        $pluginSelectList = collect(array_merge($defaultPlugins, $customPlugins))
            ->mapWithKeys(function ($pluginClass) {
                return [str($pluginClass)->afterLast('\\')->__toString() => $pluginClass];
            })
            ->toArray();

        $pluginsToEnable = multiselect(
            'Select the plugins you want to enable:',
            $pluginSelectList,
            $activePlugins);


        $pluginsToEnable = collect($pluginsToEnable)
            ->map(function ($pluginClass) use ($pluginSelectList) {
                return $pluginSelectList[$pluginClass];
            })
            ->values()
            ->toArray();

        config(['cloakr.request_plugins' => $pluginsToEnable]);

        $this->modifyConfigurationFile($pluginsToEnable);

        render("<div class='ml-3'>✔ Request plugins have been updated.</div>");
    }

    protected function addPlugin(string $class): void {

        if (!str($class)->contains('\\')) {
            $this->renderWarning("<span class='font-bold'>$class</span> is not a fully qualified class name. Please try something line <span class='font-bold'>plugins:manage --add=App\\\Logger\\\Plugins\\\MyCustomPlugin</span>.");
            return;
        }

        $pluginsToEnable = collect(config('cloakr.request_plugins'))
            ->prepend($class)
            ->unique()
            ->values()
            ->toArray();

        config(['cloakr.request_plugins' => $pluginsToEnable]);

        $this->modifyConfigurationFile($pluginsToEnable);
    }


    protected function modifyConfigurationFile(array $pluginsToEnable): void
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
