<?php

namespace App\Commands;

use App\Commands\Concerns\RendersBanner;
use App\Logger\Concerns\PluginAware;
use LaravelZero\Framework\Commands\Command;
use function Laravel\Prompts\table;
use function Termwind\render;

class ListPluginsCommand extends Command
{

    use PluginAware;
    use RendersBanner;

    protected $signature = 'plugins';

    protected $description = 'List all active request plugins.';

    public function handle()
    {

        $this->renderBanner();

        render('<div class="ml-3">Explanation text about request plugins goes here.</div>'); // TODO:

        $defaultPlugins = $this->loadDefaultPlugins();

        $customPlugins = $this->loadCustomPlugins();
        $this->ensureValidPluginConfig();


        $pluginTable = collect(array_merge($defaultPlugins, $customPlugins))
            ->map(function ($pluginClass) use ($defaultPlugins) {
                return [
                    'Plugin' => $pluginClass,
                    'Type' => in_array($pluginClass, $defaultPlugins) ? 'Default' : 'Custom',
                    'Active' => in_array($pluginClass, config('cloakr.request_plugins')) ? '✔' : '✘',
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
