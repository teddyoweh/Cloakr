<?php

namespace Cloakr\Client\Commands;

use Cloakr\Client\Contracts\FetchesPlatformDataContract;
use Cloakr\Client\Traits\FetchesPlatformData;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

use Symfony\Component\Console\Input\InputDefinition;
use function Cloakr\Common\banner;
use function Cloakr\Common\headline;
use function Cloakr\Common\lineTable;
use function Cloakr\Common\lineTableLabel;
use function Cloakr\Common\newLine;
use function \Cloakr\Common\info;
use function Laravel\Prompts\table;

class InfoCommand extends ServerAwareCommand implements FetchesPlatformDataContract
{
    use FetchesPlatformData;

    protected $signature = 'info {--json} {--servers} {--custom-domains}';

    protected $description = 'Displays the current configuration for Cloakr.';

    protected array $configuration = [];
    protected array $availableServers = [];
    protected array $customDomains = [];

    public function handle()
    {
        if ($this->option('servers')) {
            $this->getAvailableServers();
        }

        if ($this->option('custom-domains')) {
            $this->getCustomDomains();
        }

        $this->getConfiguration();

        if ($this->option('json')) {
            $this->line(json_encode(array_merge($this->configuration, $this->availableServers, $this->customDomains)));
            return;
        }

        $this->printConfiguration();
        $this->printAvailableServers();
        $this->printCustomDomains();
    }

    protected function printConfiguration(): void
    {
        banner();
        headline('Configuration');
        newLine();

        $configuration = collect($this->configuration)->mapWithKeys(function ($value, $key) {
            return [lineTableLabel($key) => lineTableLabel($value)];
        })->toArray();

        lineTable($configuration);
    }

    protected function printAvailableServers(): void
    {
        if (!$this->option('servers')) {
            return;
        }

        $servers = collect($this->availableServers['servers'])->map(function ($server) {
            unset($server['available']);
            return $server;
        });
        newLine();

        headline('Available Servers');
        info('You can connect to a specific server with the --server=key option or set this server as default with the default-server command.');

        table(['Key', 'Region', 'Type'], $servers);
    }

    protected function printCustomDomains(): void
    {
        if (!$this->option('custom-domains')) {
            return;
        }
        newLine();

        headline('Custom Domains');

        if($this->isProToken() && count($this->customDomains['custom_domains']) === 0) {
            info('You do not have any custom domains.');
            return;
        }
        if(!$this->isProToken()) {
            info('You can use custom domains with Cloakr Pro.');
            return;
        }

        info('Connect to your custom domains with the --domain=domain option or set a default domain with the default-domain command.');

        table(['Domain', 'Server'], $this->customDomains['custom_domains']);
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

    protected function getConfiguration(): void
    {
        $this->configuration = [
            "token" => config('cloakr.auth_token'),
            "default_server" => config('cloakr.default_server'),
            "default_domain" => config('cloakr.default_domain'),
            "plan" => $this->isProToken() ? "pro" : "free",
            "version" => $this->getVersion(),
            "latency" => $this->checkLatency(config('cloakr.default_server')) . "ms"
        ];
    }

    protected function getAvailableServers(): void
    {
        $servers = collect($this->lookupRemoteServers())->map(function ($server) {
            return [
                'key' => $server['key'],
                'region' => $server['region'],
                'plan' => ucfirst($server['plan']),
                'available' => $this->isProToken() || $server['plan'] === 'free',
            ];
        });

        $this->availableServers = ['servers' => $servers->toArray()];
    }

    protected function getCustomDomains(): void
    {
        $this->customDomains = ['custom_domains' => $this->getTeamDomains()->toArray()];
    }

    protected function getVersion(): string
    {
        return 'v' . config('app.version');
    }

    public function getToken(): string
    {
        return config('cloakr.auth_token');
    }


    public function __construct()
    {
        parent::__construct();

        // Remove inherited signature from ServerAwareCommand since it's not necessary here and
        // might be confusing in the help command list.
        $definition = $this->getDefinition();
        $newDefinition = new InputDefinition();

        foreach ($definition->getOptions() as $name => $option) {
            if (!in_array($name, ['server', 'server-host', 'server-port'])) {
                $newDefinition->addOption($option);
            }
        }

        foreach ($definition->getArguments() as $argument) {
            $newDefinition->addArgument($argument);
        }

        $this->setDefinition($newDefinition);
    }
}
