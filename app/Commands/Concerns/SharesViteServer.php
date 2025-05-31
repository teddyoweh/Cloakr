<?php

namespace Cloakr\Client\Commands\Concerns;

use Illuminate\Support\Str;
use React\ChildProcess\Process;
use React\EventLoop\LoopInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\PhpExecutableFinder;

trait SharesViteServer
{
    protected $originalViteServer;

    protected $isSharingVite = false;

    protected $checkForViteTimer;

    /** @var Process */
    protected $viteProcess;

    protected $watchHotFileTimer;

    protected $sharedViteURL = '';

    protected $sharedDirectory = '';

    protected function shareViteServer($hmrServer)
    {
        $this->info("Vite HMR server detected…", OutputInterface::VERBOSITY_VERBOSE);

        $phpBinary = (new PhpExecutableFinder())->find();
        if (!$phpBinary) {
            $this->error('Unable to find PHP binary to run the Vite server. Skipping.', OutputInterface::VERBOSITY_VERBOSE);
            return;
        }

        if ($hmrServer === "") {
            return;
        }

        $self = \Phar::running(false) ?: $_SERVER['PHP_SELF'];

        $arguments = [
            '"' . $phpBinary . '"',
            $self,
            'share',
            $hmrServer,
            '--prevent-cors',
            '--server=' . $this->option('server'),
            '--server-host=' . $this->option('server-host'),
            '--server-port=' . $this->option('server-port'),
            '--auth=' . $this->option('auth'),
            '--basicAuth=' . $this->option('basicAuth'),
            '--dns=' . $this->option('dns'),
            '--domain=' . $this->option('domain'),
            '--subdomain=' . strtolower(Str::random()),
        ];

        $arguments = array_filter($arguments, function ($argument) {
            $value = explode('=', $argument);
            $argument = $value[1] ?? $value[0];
            return $argument !== '';
        });

        $command = implode(' ', $arguments);

        $this->viteProcess = new Process($command);
        $this->viteProcess->start(app(LoopInterface::class));
        $this->viteProcess->stdout->on('data', function ($output) {
            if (preg_match('/Public URL\s+(.*)/', $output, $matches)) {
                $this->sharedViteURL = $matches[1];
                $this->sharedViteURL = preg_replace('/[^a-zA-Z0-9.\/:-]/', '', $this->sharedViteURL);

                $this->info('Found shared Vite server at: ' . $this->sharedViteURL, OutputInterface::VERBOSITY_VERBOSE);
                $this->replaceViteServer();
            }
        });
        $this->viteProcess->stderr->on('data', function ($output) {
            $this->error($output, OutputInterface::VERBOSITY_VERBOSE);
        });
    }

    protected function checkForVite($directory)
    {
        $this->sharedDirectory = $directory;

        $this->checkForViteTimer = app(LoopInterface::class)->addPeriodicTimer(1, function () {
            if ($this->shouldShareVite() && !$this->isSharingVite) {
                $this->info('Sharing Vite server…', OutputInterface::VERBOSITY_VERBOSE);
                $this->isSharingVite = true;
                $this->shareViteServer($this->viteServerHost());

                $this->watchHotFileTimer = app(LoopInterface::class)->addPeriodicTimer(1, function () {
                    $hotFile = $this->getViteHotFile();
                    if (!file_exists($hotFile)) {
                        return;
                    }

                    if (file_get_contents($this->getViteHotFile()) !== $this->sharedViteURL) {
                        $this->info('Change detected in Vite server URL…', OutputInterface::VERBOSITY_VERBOSE);
                        $this->replaceViteServer();
                    }
                });
            }

            if (!$this->shouldShareVite() && $this->isSharingVite) {
                $this->isSharingVite = false;
                $this->info('Stopping Vite server…', OutputInterface::VERBOSITY_VERBOSE);
                $this->viteProcess->terminate();
            }
        });
    }

    protected function shouldShareVite(): bool
    {
        return file_exists($this->getViteHotFile());
    }

    protected function viteServerHost(): string
    {
        $host = file_get_contents($this->getViteHotFile());
        $host = str_replace('[::1]', 'localhost', $host);
        return $host;
    }

    protected function replaceViteServer()
    {
        $this->info('Replacing Vite server URL in public/hot file…', OutputInterface::VERBOSITY_VERBOSE);

        $this->originalViteServer = file_get_contents($this->getViteHotFile());

        $viteServerFile = $this->getViteHotFile();
        file_put_contents($viteServerFile, $this->sharedViteURL);

        if (!defined('SIGINT')) {
            return;
        }

        app(LoopInterface::class)->addSignal(SIGINT, $func = function ($signal) use (&$func, $viteServerFile) {
            $this->revertViteServerFile();
            app(LoopInterface::class)->removeSignal(SIGINT, $func);
            exit(0);
        });
    }

    protected function revertViteServerFile()
    {
        $viteServerFile = $this->getViteHotFile();

        if (file_exists($viteServerFile)) {
            file_put_contents($viteServerFile, $this->originalViteServer);
        }
    }

    protected function getViteHotFile(): string
    {
        return $this->sharedDirectory . '/public/hot';
    }
}
