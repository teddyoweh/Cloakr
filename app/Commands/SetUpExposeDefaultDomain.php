<?php

namespace Cloakr\Client\Commands;


use Cloakr\Client\Contracts\FetchesPlatformDataContract;
use Cloakr\Client\Traits\FetchesPlatformData;

use Illuminate\Support\Facades\Artisan;

use function Cloakr\Common\warning;
use function Cloakr\Common\info;
use function Cloakr\Common\headline;
use function Laravel\Prompts\select;

class SetUpCloakrDefaultDomain implements FetchesPlatformDataContract
{

    use FetchesPlatformData;

    protected string $token;

    public function __invoke(string $token)
    {
        if (!$this->cloakrPlatformSetup()) return;

        $this->token = $token;

        headline('Default Domain');
        info('Use your teams custom whitelabel domains for easier subdomain management and better links.');

        if ($this->isProToken()) {
            $domains = $this->getTeamDomains();

            if ($domains->isNotEmpty()) {
                info();
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
                warning('No custom domains found. You can add custom domains in the Cloakr dashboard.');
            }
        }
        else {
            info('To access to custom domains, upgrade to <a href="https://cloakr.dev">Cloakr Pro</a>.');
        }
    }

    public function getToken(): string
    {
        return $this->token;
    }
}
