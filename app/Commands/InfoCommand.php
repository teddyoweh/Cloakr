<?php

namespace App\Commands;

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

    protected $signature = 'info {--json}';

    protected $description = 'Displays the current configuration for Cloakr.';

    public function handle()
    {

        if (!$this->option('json')) {
            render('<div class="ml-2 text-pink-500 font-bold"><span class="pr-0.5">></span> Cloakr</div>');
        }

        $configuration = [];

        $configuration = [
            "token" => config('cloakr.auth_token'),
            "default-server" => config('cloakr.default_server'),
            "domain-domain" => config('cloakr.default_domain'),
            "plan" => $this->isProToken() ? "pro" : "free",
            "version" => $this->getVersion(),
            "latency" => $this->checkLatency(config('cloakr.default_server')) . "ms"
        ];

        if ($this->option('json')) {
            $this->line(json_encode($configuration));
            return;
        }

        render('<div class="ml-3 font-bold">Configuration</div>');

        $template = <<<HTML
        <div class="flex ml-3 mr-6">
            <span>key</span>
            <span class="flex-1 content-repeat-[.] text-gray-800"></span>
            <span>value</span>
        </div>
HTML;

        foreach ($configuration as $key => $value) {
            $output = str_replace(
                ['key', 'value'],
                [$this->keyLabel($key), $this->valueLabel($value)],
                $template
            );

            render($output);
        }
    }

    protected function keyLabel(string $key): string
    {
        return match ($key) {
            'token' => 'Token',
            'default-server' => 'Default Server',
            'domain-domain' => 'Default Domain',
            'plan' => 'Plan',
            'version' => 'Version',
            'latency' => 'Latency',
            default => $key
        };
    }

    protected function valueLabel(string $value): string
    {
        return match ($value) {
            'free' => 'Cloakr Free',
            'pro' => 'Cloakr Pro',
            default => $value
        };
    }

    protected function checkLatency(string $server): int
    {

        if ($server === "free") {
            $host = "sharedwithcloakr.com";
        } else {
            $host = "cloakr.{$server}.sharedwithcloakr.com";
        }

        try {
            $startTime = microtime(true);
            Http::timeout(5)->get($host);
            $latency = microtime(true) - $startTime;

            return round($latency * 1000);
        } catch (Exception $e) {
            if ($this->option("verbose")) {
                render("<div class='ml-3 px-2 text-orange-600 bg-orange-100'>Error while checking latency: {$e->getMessage()}</div>");
            }

            return 999;
        }
    }

    protected function getVersion(): string {
        Artisan::call("help --version");
        $version = Artisan::output();
        return 'v'.str()->after($version, "Cloakr ");
    }

    public function getToken()
    {
        return config('cloakr.auth_token');
    }
}
