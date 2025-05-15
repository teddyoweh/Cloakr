<?php

namespace App\Commands;

use App\Commands\Concerns\RendersBanner;
use App\Contracts\FetchesPlatformDataContract;
use App\Traits\FetchesPlatformData;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

use function Termwind\render;

class InfoCommand extends Command implements FetchesPlatformDataContract
{
    use FetchesPlatformData;
    use RendersBanner;

    protected $signature = 'info {--json}';

    protected $description = 'Displays the current configuration for Cloakr.';

    public function handle()
    {

        if (!$this->option('json')) {
            $this->renderBanner();
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

        render('<div class="ml-3 font-bold">Configuration</div>');

        $configuration = collect($configuration)->mapWithKeys(function ($value, $key) {
            return [lineTableLabel($key) => lineTableLabel($value)];
        })->toArray();

        renderLineTable($configuration);
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
            return $result->handlerStats()['connect_time'] * 1000;
        } catch (Exception $e) {
            if ($this->option("verbose")) {
                render("<div class='ml-3 px-2 text-orange-600 bg-orange-100'>Error while checking latency: {$e->getMessage()}</div>");
            }

            return 999;
        }
    }

    protected function getVersion(): string {
        return 'v'.config('app.version');
    }

    public function getToken()
    {
        return config('cloakr.auth_token');
    }
}
