<?php

namespace Cloakr\Client\Commands;

use Cloakr\Client\Commands\Concerns\RendersBanner;
use Cloakr\Client\Commands\Concerns\RendersOutput;
use Cloakr\Client\Logger\Plugins\PluginManager;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\text;
use function Termwind\render;

class CreateCustomPluginCommand extends Command
{
    use RendersBanner, RendersOutput;

    protected $signature = 'make:plugin';

    protected $description = 'Create a new custom request plugin.';

    public function handle(PluginManager $pluginManager)
    {

        $this->renderBanner();

        render('<div class="ml-3 mb-1">Check out the documentation at ... to learn how to create your custom request plugin.</div>'); // TODO:

        $pluginName = text(
            label: 'What is the name of the plugin?',
            placeholder: 'MyCustomPlugin',
            required: true
        );

        $pluginName = preg_replace('/[^a-zA-Z0-9]/', '', $pluginName);

        $customPluginDirectory = $pluginManager->getCustomPluginDirectory();

        $pluginFile = implode(DIRECTORY_SEPARATOR, [$customPluginDirectory, $pluginName . '.php']);

        if (file_exists($pluginFile)) {
            $this->renderWarning("The file at $pluginFile already exists.");
            return;
        }

        $pluginStub = file_get_contents(base_path('resources/stubs/CustomRequestPlugin.php.stub'));
        $pluginStub = str_replace('__CLASSNAME__', $pluginName, $pluginStub);

        file_put_contents($pluginFile, $pluginStub);

        render('<div class="ml-3">✔ The plugin has been created at ' . $pluginFile . ' and added to the configuration.</div>');

        $this->call('plugins:manage', ['--add' => 'Cloakr\\Client\\Logger\\Plugins\\' . $pluginName]);

    }

}
