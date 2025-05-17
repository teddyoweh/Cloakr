<?php

namespace Cloakr\Client\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

use function Cloakr\Common\banner;
use function Cloakr\Common\lineTable;
use function Cloakr\Common\lineTableLabel;
use function Cloakr\Common\warning;
use function Cloakr\Common\info;
use function Cloakr\Common\headline;
use function Laravel\Prompts\confirm;

class RollbackTokenCommand extends Command
{


    protected $signature = 'token:rollback';

    protected $description = 'Rollback the Cloakr token and setup to the previous version, if applicable.';

    protected string $previousSetupPath;

    public function handle()
    {

        $this->previousSetupPath = implode(DIRECTORY_SEPARATOR, [
            $_SERVER['HOME'] ?? $_SERVER['USERPROFILE'],
            '.cloakr',
            'previous_setup.json',
        ]);

        banner();

        if (!file_exists($this->previousSetupPath)) {
            warning('No previous setup found.');
            return;
        }

        headline('Previous Setup');

        $previousSetup = json_decode(file_get_contents($this->previousSetupPath), true);

        $previousSetupTable = collect($previousSetup)->mapWithKeys(function ($value, $key) {
            return [lineTableLabel($key) => lineTableLabel($value)];
        })->toArray();

        lineTable($previousSetupTable);

        if (!confirm("Do you want to rollback your Cloakr setup to the previous state?", false)) {
            return;
        }

        $this->rememberPreviousSetup();

        $token = $previousSetup['token'];

        Artisan::call("token $token --no-interaction");

        info("✔ Set Cloakr token to <span class='font-bold'>$token</span>.");

        if ($domain = $previousSetup['default_domain']) {
            Artisan::call("default-domain $domain");
        }

        if ($server = $previousSetup['default_server']) {
            Artisan::call("default-server $server");
        }

        Artisan::output();
    }


    protected function rememberPreviousSetup()
    {
        $previousSetup = [
            'token' => config('cloakr.auth_token'),
            'default_server' => config('cloakr.default_server'),
            'default_domain' => config('cloakr.default_domain'),
        ];

        file_put_contents($this->previousSetupPath, json_encode($previousSetup));
    }
}
