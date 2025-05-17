<?php

namespace App\Commands;

use App\Commands\Concerns\RendersBanner;
use App\Logger\Concerns\PluginAware;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\text;
use function Termwind\render;

class CreateCustomPluginCommand extends Command
{
    use RendersBanner;
    use PluginAware;

    protected $signature = 'make:plugin';

    protected $description = 'Create a new custom request plugin.';

    public function handle()
    {

        $this->renderBanner();

        render('<div class="ml-3 mb-1">Check out the documentation at ... to learn how to create your custom request plugin.</div>'); // TODO:

        $pluginName = text(
            label: 'What is the name of the plugin?',
            placeholder: 'MyCustomPlugin',
            required: true
        );

        $customPluginDirectory = $this->getCustomPluginDirectory();

        $pluginFile = implode(DIRECTORY_SEPARATOR, [$customPluginDirectory, $pluginName . '.php']);

        if (file_exists($pluginFile)) {
            render('<div class="ml-3 px-2 text-orange-600 bg-orange-100">The file at ' . $pluginFile . ' already exists.</div>');
            return;
        }

        $pluginStub = file_get_contents(base_path('resources/stubs/CustomRequestPlugin.php.stub'));
        $pluginStub = str_replace('__CLASSNAME__', $pluginName, $pluginStub);

        file_put_contents($pluginFile, $pluginStub);

        render('<div class="ml-3">✔ The plugin has been created at ' . $pluginFile . '.</div>');

        // TODO: Add the plugin to the config file

    }

}
