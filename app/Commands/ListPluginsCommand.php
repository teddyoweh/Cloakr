<?php

namespace Cloakr\Client\Commands;

use Cloakr\Client\Commands\Concerns\RendersBanner;
use Cloakr\Client\Logger\Plugins\PluginManager;
use LaravelZero\Framework\Commands\Command;
use function Laravel\Prompts\table;
use function Termwind\render;

class ListPluginsCommand extends Command
{
    use RendersBanner;

    protected $signature = 'plugins';

    protected $description = 'List all active request plugins.';

    public function handle(PluginManager $pluginManager)
    {

        $this->renderBanner();

        render('<div class="ml-3">Explanation text about request plugins goes here.</div>'); // TODO:

        $defaultPlugins = $pluginManager->getDefaultPlugins();
        $customPlugins = $pluginManager->getCustomPlugins();

        $pluginTable = collect(array_merge($defaultPlugins, $customPlugins))
            ->map(function ($pluginClass) use ($defaultPlugins, $pluginManager) {
                return [
                    'Plugin' => $pluginClass,
                    'Type' => in_array($pluginClass, $defaultPlugins) ? 'Default' : 'Custom',
                    'Active' => $pluginManager->isEnabled($pluginClass) ? '✔' : '✘',
                ];
            })
            ->toArray();


        table(
            headers: ['Plugin', 'Type', 'Active'],
            rows: $pluginTable
        );

        render('<div class="ml-3 mb-1">Use the <span class="font-bold">plugins:manage</span> command to activate and deactivate request plugins.</div>');
    }

}
