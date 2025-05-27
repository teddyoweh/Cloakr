<?php

namespace Cloakr\Client\Commands;


use Cloakr\Client\Factory;
use chillerlan\QRCode\Common\Version;
use chillerlan\QRCode\Data\QRMatrix;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Str;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Http\HttpServer;
use React\Http\Message\Response;
use React\Socket\SocketServer;
use Symfony\Component\Console\Output\OutputInterface;

use function Cloakr\Common\banner;
use function Cloakr\Common\error;
use function Cloakr\Common\info;
use function Termwind\render;

class ShareCatchAllCommand extends ShareCommand
{


    protected $signature = 'catch-all {host?} {--subdomain=} {--auth=} {--basicAuth=} {--dns=} {--domain=} {--qr} {--qr-code}';

    protected $description = 'Share a catch-all site';

    public function handle()
    {
        $this->input->setArgument('host', $this->startCatchAllServer());

        parent::handle();
    }

    protected function startCatchAllServer(): string
    {
        $http = new HttpServer(function (ServerRequestInterface $request) {
            return Response::plaintext(
                "OK"
            );
        });

        $socket = new SocketServer('127.0.0.1:0');
        $http->listen($socket);

        $url = str_replace('tcp:', 'http:', $socket->getAddress());

        info("Catch-All Server is listening on http://{$url}", options: OutputInterface::VERBOSITY_VERBOSE);

        return $url;
    }
}
