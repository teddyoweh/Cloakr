<?php

namespace Cloakr\Client\Commands;

use Cloakr\Client\Exceptions\InvalidServerProvided;
use Cloakr\Client\Logger\CliLogger;
use Illuminate\Console\Parser;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Console\Output\ConsoleOutput;

abstract class ServerAwareCommand extends Command
{
    const DEFAULT_HOSTNAME = 'sharedwithcloakr.com';
    const DEFAULT_PORT = 443;
    const DEFAULT_SERVER_ENDPOINT = 'https://cloakr.dev/api/servers';

    public function __construct()
    {
        parent::__construct();

        $inheritedSignature = '{--server=} {--server-host=} {--server-port=}';

        $this->getDefinition()->addOptions(Parser::parse($inheritedSignature)[2]);

        $this->configureConnectionLogger();
    }

    protected function configureConnectionLogger()
    {
        app()->singleton(CliLogger::class, function () {
            return new CliLogger(new ConsoleOutput());
        });

        return $this;
    }

    protected function getServerHost()
    {
        if ($this->option('server-host')) {
            return $this->option('server-host');
        }

        /**
         * Try to find the server in the servers array.
         * If no array exists at all (when upgrading from v1),
         * always return sharedwithcloakr.com.
         */
        if (config('cloakr.servers') === null) {
            return static::DEFAULT_HOSTNAME;
        }

        $server = $this->option('server') ?? config('cloakr.default_server');
        $host = config('cloakr.servers.'.$server.'.host');

        if (! is_null($host)) {
            return $host;
        }

        return $this->lookupRemoteServerHost($server);
    }

    protected function getServerPort()
    {
        if ($this->option('server-port')) {
            return $this->option('server-port');
        }

        /**
         * Try to find the server in the servers array.
         * If no array exists at all (when upgrading from v1),
         * always return sharedwithcloakr.com.
         */
        if (config('cloakr.servers') === null) {
            return static::DEFAULT_PORT;
        }

        $server = $this->option('server') ?? config('cloakr.default_server');
        $host = config('cloakr.servers.'.$server.'.port');

        if (! is_null($host)) {
            return $host;
        }

        return $this->lookupRemoteServerPort($server);
    }

    protected function lookupRemoteServers()
    {
        try {
            return Http::withOptions([
                'verify' => false,
            ])->get(
                config('cloakr.server_endpoint', static::DEFAULT_SERVER_ENDPOINT)
            )->json();
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected function lookupRemoteServerHost($server)
    {
        $servers = $this->lookupRemoteServers();
        $host = Arr::get($servers, $server.'.host');

        throw_if(is_null($host), new InvalidServerProvided($server));

        return $host;
    }

    protected function lookupRemoteServerPort($server)
    {
        $servers = $this->lookupRemoteServers();
        $port = Arr::get($servers, $server.'.port');

        throw_if(is_null($port), new InvalidServerProvided($server));

        return $port;
    }
}
