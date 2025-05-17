<?php

namespace App\Commands;


use App\Commands\Concerns\RendersOutput;
use App\Contracts\FetchesPlatformDataContract;
use App\Traits\FetchesPlatformData;
use Illuminate\Support\Facades\Artisan;

use function Laravel\Prompts\select;
use function Termwind\render;

class SetUpCloakrDefaultDomain implements FetchesPlatformDataContract
{
    use RendersOutput;
    use FetchesPlatformData;

    protected string $token;

    public function __invoke(string $token)
    {
        if (!$this->cloakrPlatformSetup()) return;

        $this->token = $token;

        render('<div class="ml-3 mt-1 font-bold">Default Domain</div>');
        render('<div class="ml-3">Use your teams custom whitelabel domains for easier subdomain management and better links.</div>');

        if ($this->isProToken()) {
            $domains = $this->getTeamDomains();

            if ($domains->isNotEmpty()) {
                $domain = select(
                    label: 'What default domain would you like to use?',
                    options: $domains->mapWithKeys(function ($domain) {
                        return [
                            $domain['name'] =>  $domain['name'] . ' [' . $domain['server'] . ']'
                        ];
                    }),
                    hint: "You can use `cloakr default-domain` to change this setting."
                );

                if ($domain) {
                    $server = $domains->firstWhere('name', $domain)['server'];
                    Artisan::call("default-domain $domain --server=$server");
                }
            }

            else {
                $this->renderWarning('No custom domains found. You can add custom domains in the Cloakr dashboard.');
            }
        }
        else {
            render('<div class="ml-3 mb-1">To access to custom domains, upgrade to <a href="https://cloakr.dev">Cloakr Pro</a>.');
        }
    }

    public function getToken()
    {
        return $this->token;
    }
}
