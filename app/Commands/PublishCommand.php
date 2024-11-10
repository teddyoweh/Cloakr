<?php

namespace App\Commands;

use Illuminate\Console\Command;

class PublishCommand extends Command
{
    protected $signature = 'publish';

    protected $description = 'Publish the cloakr configuration file';

    public function handle()
    {
        $configFile = implode(DIRECTORY_SEPARATOR, [
            $_SERVER['HOME'],
            '.cloakr',
            'config.php'
        ]);

        if (file_exists($configFile)) {
            $this->error('Cloakr configuration file already exists at '.$configFile);
            return;
        }

        file_put_contents($configFile, file_get_contents(base_path('config/cloakr.php')));

        $this->info('Published cloakr configuration file to: ' . $configFile);
    }
}
