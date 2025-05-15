<?php

namespace App\Commands;


use App\Contracts\FetchesPlatformDataContract;
use App\Traits\FetchesPlatformData;
use Illuminate\Support\Facades\Artisan;

use function Laravel\Prompts\select;
use function Termwind\render;

class SetUpCloakrDefaultServer implements FetchesPlatformDataContract
{
    use FetchesPlatformData;

    protected string $token;

    public function __invoke(string $token)
    {
        if (!$this->cloakrPlatformSetup()) return;

        $this->token = $token;

        $closestServer = null;
        $servers = collect();

        render('<div class="mt-1 ml-3 font-bold">Default Server</div>');

        if ($this->isProToken()) {
            $servers = $this->getServers();
            $closestServer = $this->getClosestServer();

            render('<div class="ml-3 mb-1">This token has access to our high-performance, global server network.</div>');
        } else {
            render('<div class="ml-3 mb-1">The free license is limited to the <span class="font-bold">free server (Region: Europe)</span>.
            To access our high-performance, global server network, upgrade to <a href="https://cloakr.dev/go-pro">Cloakr Pro</a>.</div>');

            Artisan::call("default-server free");
            render(Artisan::output());
        }

        if ($servers->isNotEmpty()) {
            $server = select(
                label: 'What default server would you like to use?',
                options: $servers->mapWithKeys(function ($server) {
                    return [
                        $server['key'] =>  '[' . $server['key'] . '] ' . $server['region']
                    ];
                }),
                default: $closestServer ? $closestServer['key'] : null,
                hint: "You can use `cloakr default-server` to change this setting."
            );

            if ($server) {
                Artisan::call("default-server $server");
                render(Artisan::output());
            }
        }
    }

    public function getToken()
    {
        return $this->token;
    }
}
