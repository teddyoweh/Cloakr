<?php

namespace App\Commands\Plugins;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

use function Laravel\Prompts\select;
use function Termwind\render;
use function Termwind\terminal;

class SetupCloakrProToken extends CommandPlugin
{

    protected string $token;

    protected $closestServer = [];

    protected Collection $servers;
    protected Collection $domains;


    public function __invoke(...$parameters)
    {
        if (count($parameters) <= 0) return;
        if (!$this->cloakrPlatformSetup()) return;

        $this->token = $parameters[0];

        if ($this->isProToken()) {

            render('<p class="ml-3">This token has access to our high-performance, global server network.</p>');

            $this->getTeamDomains();
            
            if ($this->domains->isNotEmpty()) {

                $domain = select(
                    label: 'What default domain would you like to use?',
                    options: $this->domains->mapWithKeys(function ($domain) {
                        return [
                            $domain['name'] =>  $domain['name'] . ' [' . $domain['server'] . ']'
                        ];
                    }),
                    hint: "You can use `cloakr default-domain` to change this setting."
                );

                dd($domain);


            } else {


                $this->getServerNetwork();

                if ($this->servers->isNotEmpty()) {

                    $server = select(
                        label: 'What default server would you like to use?',
                        options: $this->servers->mapWithKeys(function ($server) {
                            return [
                                $server['key'] =>  '[' . $server['key'] . '] ' . $server['region']
                            ];
                        }),
                        default: $this->closestServer['key'],
                        hint: "You can use `cloakr default-server` to change this setting."
                    );

                    Artisan::call("default-server $server");
                    render(Artisan::output());
                }
            }
        }
    }


    protected function isProToken(): bool
    {
        $response = Http::post($this->platformEndpoint() . 'client/is-pro-token', [
            'token' => $this->token
        ]);

        if (!$response->ok()) {
            return false;
        }

        $result = $response->json();

        if (!$result) {
            return false;
        }

        if (array_key_exists("is_pro", $result) && $result["is_pro"] === true) {
            return true;
        }

        return false;
    }

    protected function platformEndpoint(): string
    {
        return config('cloakr.platform_endpoint') . '/api/';
    }

    protected function getServerNetwork(): void
    {
        $this->closestServer = [];
        $this->servers = collect();

        $response = Http::post($this->platformEndpoint() . 'client/closest-server', [
            'token' => $this->token
        ]);

        if (!$response->ok()) {
            return;
        }

        $result = $response->json();

        if (!$result) {
            return;
        }

        if (array_key_exists('closest_server', $result)) {
            $this->closestServer = $result['closest_server'];
        }

        if (array_key_exists('servers', $result)) {
            $this->servers = collect($result['servers'])->sort();
        }
    }

    protected function getTeamDomains(): void
    {
        $this->domains = collect();

        $response = Http::post($this->platformEndpoint() . 'client/team-domains', [
            'token' => $this->token
        ]);

        if (!$response->ok()) {
            return;
        }

        $result = $response->json();

        if (!$result) {
            return;
        }

        if (array_key_exists('domains', $result)) {
            $this->domains = collect($result['domains'])->sort();
        }
    }

    protected function cloakrPlatformSetup()
    {
        return config('cloakr.platform_endpoint') !== null && config('cloakr.platform_endpoint') !== "";
    }
}
