<?php

namespace App\Commands;

use App\Client\Factory;
use App\Logger\CliRequestLogger;
use LaravelZero\Framework\Commands\Command;
use React\EventLoop\LoopInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

class SharePortCommand extends Command
{
    protected $signature = 'share-port {port} {--auth=}';

    protected $description = 'Share a local port with a remote cloakr server';

    protected function configureConnectionLogger()
    {
        app()->bind(CliRequestLogger::class, function () {
            return new CliRequestLogger(new ConsoleOutput());
        });

        return $this;
    }

    public function handle()
    {
        $this->configureConnectionLogger();

        (new Factory())
            ->setLoop(app(LoopInterface::class))
            ->setHost(config('cloakr.host', 'localhost'))
            ->setPort(config('cloakr.port', 8080))
            ->setAuth($this->option('auth'))
            ->createClient()
            ->sharePort($this->argument('port'))
            ->createHttpServer()
            ->run();
    }
}
