<?php

namespace Cloakr\Client\Commands;

use Cloakr\Client\Commands\Concerns\RendersBanner;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\table;
use function Termwind\render;

class ServerListCommand extends Command
{
    use RendersBanner;

    const DEFAULT_SERVER_ENDPOINT = 'https://cloakr.dev/api/servers';

    protected $signature = 'servers {--json}';

    protected $description = 'Set or retrieve the default server to use with Cloakr.';

    public function handle()
    {

        $servers = collect($this->lookupRemoteServers())->map(function ($server) {
            return [
                'key' => $server['key'],
                'region' => $server['region'],
                'plan' => Str::ucfirst($server['plan']),
            ];
        });

        if($this->option('json')) {
            $this->line($servers->toJson());
            return;
        }

        $this->renderBanner();

        render("<div class='ml-3'>You can connect to a specific server with the --server=key option or set this server as default with the default-server command.</div>");

        table(['Key', 'Region', 'Type'], $servers);
    }

    protected function lookupRemoteServers()
    {
        try {
            return Http::withOptions([
                'verify' => false,
            ])->get(config('cloakr.server_endpoint', static::DEFAULT_SERVER_ENDPOINT))->json();
        } catch (\Throwable $e) {
            return [];
        }
    }
}
