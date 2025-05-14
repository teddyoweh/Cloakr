<?php

namespace App\Commands\Plugins;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function Termwind\render;
use function Termwind\terminal;

class SetupCloakrProToken extends CommandPlugin
{

    protected string $token;

    protected $closestServer = [];
    protected Collection $servers;

    public function __invoke(...$parameters)
    {
        if (count($parameters) <= 0) return;
        if (!$this->cloakrPlatformSetup()) return;

        // TODO: clear default domain and server, only set new values when process is not aborted somewhere

        $this->token = $parameters[0];

        if ($this->tokenConfigExists()) {
            render("<p class='ml-3'>Found existing configuration for Cloakr token <span class='font-bold'>$this->token</span>.</p>");

            $data = [
                "Team" => "Beyond Code",
                "Default Domain" => "share.idontcare.lol",
                "Default Server" => "Europe 1 (eu-1)",
            ];

            $this->renderLineTable($data);

            $result = confirm("Would you like to apply the existing configuration?");

            // TODO:
        } else {

            if ($this->isProToken()) {

                $this->getServerNetwork();

                if ($this->servers->isNotEmpty()) {
                    render('<p class="ml-3">This token has access to our high-performance, global server network.</p>');

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

                // TODO: Fetch team domains and set default-domain
            }
        }
    }

    protected function renderLineTable($data)
    {
        $terminalWidth = terminal()->width();

        $template = '<div class="ml-3"><span class="font-bold">$key</span><span class="text-gray-500">...</span>$value</div>';

        foreach ($data as $key => $value) {
            $keyLength = strlen($key);
            $valueLength = strlen($value);

            $dotsNeeded = max($terminalWidth - $keyLength - $valueLength - 10, 0);
            $dots = str_repeat('.', $dotsNeeded);

            $output = str_replace(
                ['$key', '...', '$value'],
                [$key, $dots, $value],
                $template
            );

            render($output);
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

        if (array_key_exists("is_pro", $result) && $result["is_pro"] === true) {
            return true;
        }

        return false;
    }

    protected function platformEndpoint(): string
    {
        return config('cloakr.platform_endpoint') . '/api/';
    }

    protected function tokenConfigExists(): bool
    {
        if ($this->token === "c1b4a91a-a0cb-41c6-9b44-99f854acecb4") return true;
        if ($this->token === "c1b4a91a-a0cb-41c6-9b44-99f854acecb5") return false;
        return false; // TODO: Request to cloakr-platform?
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

        if(!$result) {
            return;
        }

        if (array_key_exists('closest_server', $result)) {
            $this->closestServer = $result['closest_server'];
        }

        if (array_key_exists('servers', $result)) {
            $this->servers = collect($result['servers'])->sort();
        }
    }

    protected function cloakrPlatformSetup()
    {
        return config('cloakr.platform_endpoint') !== null && config('cloakr.platform_endpoint') !== "";
    }
}
