<?php

namespace App\Commands;

use Illuminate\Console\Command;

class StoreAuthenticationTokenCommand extends Command
{
    protected $signature = 'token {token?}';

    protected $description = 'Set or retrieve the authentication token to use with cloakr.';

    public function handle()
    {
        $config = config('cloakr', []);

        if (!is_null($this->argument('token'))) {
            $this->info('Setting the cloakr authentication token to "' . $this->argument('token') . '"');

            $config['auth_token'] = $this->argument('token');

            $configFile = implode(DIRECTORY_SEPARATOR, [
                $_SERVER['HOME'],
                '.cloakr',
                'config.php'
            ]);

            @mkdir(dirname($configFile), 0777, true);

            file_put_contents($configFile, '<?php return ' . var_export($config, true) . ';');
            return;
        }

        if (is_null($token = config('cloakr.auth_token'))) {
            $this->info('There is no authentication token specified.');
        } else {
            $this->info('Current authentication token: ' . $token);
        }
    }
}
