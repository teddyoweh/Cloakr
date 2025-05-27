<?php

namespace Cloakr\Client\Commands;

use Cloakr\Client\Contracts\FetchesPlatformDataContract;
use Cloakr\Client\Traits\FetchesPlatformData;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

use function Cloakr\Common\banner;
use function Cloakr\Common\headline;
use function Cloakr\Common\lineTable;
use function Cloakr\Common\lineTableLabel;

class InfoCommand extends Command implements FetchesPlatformDataContract
{
    use FetchesPlatformData;

    protected $signature = 'info {--json}';

    protected $description = 'Displays the current configuration for Cloakr.';

    public function handle()
    {

        if (!$this->option('json')) {
            banner();
        }

        $configuration = [];

        $configuration = [
            "token" => config('cloakr.auth_token'),
            "default_server" => config('cloakr.default_server'),
            "default_domain" => config('cloakr.default_domain'),
            "plan" => $this->isProToken() ? "pro" : "free",
            "version" => $this->getVersion(),
            "latency" => $this->checkLatency(config('cloakr.default_server')) . "ms"
        ];

        if ($this->option('json')) {
            $this->line(json_encode($configuration));
            return;
        }

        headline('Configuration');

        $configuration = collect($configuration)->mapWithKeys(function ($value, $key) {
            return [lineTableLabel($key) => lineTableLabel($value)];
        })->toArray();

        lineTable($configuration);
    }

    protected function checkLatency(string $server): int
    {

        if ($server === "free") {
            $host = "sharedwithcloakr.com";
        } else {
            $host = "cloakr.{$server}.sharedwithcloakr.com";
        }

        try {
            $result = Http::timeout(5)->get($host);
            return round($result->handlerStats()['connect_time'] * 1000);
        } catch (Exception $e) {
            if ($this->option("verbose")) {
                warning("Error while checking latency: {$e->getMessage()}");
            }

            return 999;
        }
    }

    protected function getVersion(): string {
        return 'v'.config('app.version');
    }

    public function getToken(): string
    {
        return config('cloakr.auth_token');
    }
}
