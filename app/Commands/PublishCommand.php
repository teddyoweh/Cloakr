<?php

namespace Cloakr\Client\Commands;

use Illuminate\Console\Command;

class PublishCommand extends Command
{
    protected $signature = 'publish {--force}';

    protected $description = 'Publish the cloakr configuration file';

    public function handle()
    {
        $configFile = implode(DIRECTORY_SEPARATOR, [
            $_SERVER['HOME'] ?? $_SERVER['USERPROFILE'],
            '.cloakr',
            'config.php',
        ]);

        if (! $this->option('force') && file_exists($configFile)) {
            $this->error('Cloakr configuration file already exists at '.$configFile);

            return;
        }

        @mkdir(dirname($configFile), 0755, true);
        file_put_contents($configFile, file_get_contents(base_path('config/cloakr.php')));

        $this->info('Published cloakr configuration file to: '.$configFile);
    }
}
