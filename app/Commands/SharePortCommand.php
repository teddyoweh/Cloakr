<?php

namespace Cloakr\Client\Commands;

use Cloakr\Client\Factory;
use React\EventLoop\LoopInterface;

class SharePortCommand extends ServerAwareCommand
{
    protected $signature = 'share-port {port} {--auth=}';

    protected $description = 'Share a local port with a remote cloakr server';

    public function handle()
    {
        $auth = $this->option('auth') ?? config('cloakr.auth_token', '');

        (new Factory())
            ->setLoop(app(LoopInterface::class))
            ->setHost($this->getServerHost())
            ->setPort($this->getServerPort())
            ->setAuth($auth)
            ->createClient()
            ->sharePort($this->argument('port'))
            ->createHttpServer()
            ->run();
    }
}
