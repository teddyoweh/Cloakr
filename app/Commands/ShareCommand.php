<?php

namespace App\Commands;

use App\Client\Factory;
use React\EventLoop\LoopInterface;

class ShareCommand extends ServerAwareCommand
{
    protected $signature = 'share {host} {--subdomain=} {--auth=} {--dns=} {--domain=}';

    protected $description = 'Share a local url with a remote cloakr server';

    public function handle()
    {
        $auth = $this->option('auth') ?? config('cloakr.auth_token', '');

        if (strstr($this->argument('host'), 'host.docker.internal')) {
            config(['cloakr.dns' => true]);
        }

        if ($this->option('dns') !== null) {
            config(['cloakr.dns' => empty($this->option('dns')) ? true : $this->option('dns')]);
        }

        $domain = config('cloakr.default_domain');

        if (! is_null($this->option('server'))) {
            $domain = null;
        }

        if (! is_null($this->option('domain'))) {
            $domain = $this->option('domain');
        }

        (new Factory())
            ->setLoop(app(LoopInterface::class))
            ->setHost($this->getServerHost())
            ->setPort($this->getServerPort())
            ->setAuth($auth)
            ->createClient()
            ->share(
                $this->argument('host'),
                explode(',', $this->option('subdomain')),
                $domain
            )
            ->createHttpServer()
            ->run();
    }
}
