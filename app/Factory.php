<?php

namespace Cloakr\Client;

use Cloakr\Client\Http\App;
use Cloakr\Client\Http\ClientRouteGenerator;
use Cloakr\Client\Http\Controllers\ClearLogsController;
use Cloakr\Client\Http\Controllers\CreateTunnelController;
use Cloakr\Client\Http\Controllers\DashboardController;
use Cloakr\Client\Http\Controllers\GetTunnelsController;
use Cloakr\Client\Http\Controllers\GetLogController;
use Cloakr\Client\Http\Controllers\GetLogsController;
use Cloakr\Client\Http\Controllers\PushLogsToDashboardController;
use Cloakr\Client\Http\Controllers\ReplayLogController;
use Cloakr\Client\Http\Controllers\SearchLogsController;
use Cloakr\Client\WebSockets\Socket;
use Cloakr\Client\Http\Controllers\ReplayModifiedLogController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Termwind\Termwind;
use function Cloakr\Common\info;

class Factory
{
    /** @var string */
    protected $host = 'localhost';

    /** @var int */
    protected $port = 8080;

    /** @var string */
    protected $auth = '';

    /** @var string */
    protected $basicAuth;

    /** @var \React\EventLoop\LoopInterface */
    protected $loop;

    /** @var App */
    protected $app;

    /** @var ClientRouteGenerator */
    protected $router;

    public function __construct()
    {
        $this->loop = Loop::get();
        $this->router = new ClientRouteGenerator();
    }

    public function setHost(string $host)
    {
        $this->host = $host;

        return $this;
    }

    public function setPort(int $port)
    {
        $this->port = $port;

        return $this;
    }

    public function setAuth(?string $auth)
    {
        $this->auth = $auth;

        return $this;
    }

    public function setBasicAuth(?string $basicAuth)
    {
        $this->basicAuth = $basicAuth;

        return $this;
    }

    public function setLoop(LoopInterface $loop)
    {
        $this->loop = $loop;

        return $this;
    }

    protected function bindConfiguration()
    {
        app()->singleton(Configuration::class, function ($app) {
            return new Configuration($this->host, $this->port, $this->auth, $this->basicAuth);
        });
    }

    protected function bindClient()
    {
        app()->singleton('cloakr.client', function ($app) {
            return $app->make(Client::class);
        });
    }

    protected function bindProxyManager()
    {
        app()->bind(ProxyManager::class, function ($app) {
            return new ProxyManager($app->make(Configuration::class), $this->loop);
        });
    }

    public function createClient()
    {
        $this->bindClient();

        $this->bindConfiguration();

        $this->bindProxyManager();

        $this->migrateDatabase();

        return $this;
    }

    public function share($sharedUrl, $subdomain = null, $serverHost = null)
    {
        app('cloakr.client')->share($sharedUrl, $subdomain, $serverHost);

        return $this;
    }

    public function sharePort(int $port)
    {
        app('cloakr.client')->sharePort($port);

        return $this;
    }

    protected function addRoutes()
    {
        $this->router->get('/', DashboardController::class);

        $this->router->addPublicFilesystem();

        $this->router->get('/api/tunnels', GetTunnelsController::class);
        $this->router->post('/api/tunnel', CreateTunnelController::class);
        $this->router->get('/api/logs', GetLogsController::class);
        $this->router->get('/api/log/{log}', GetLogController::class);
        $this->router->post('/api/logs/search', SearchLogsController::class);
        $this->router->post('/api/logs', PushLogsToDashboardController::class);
        $this->router->get('/api/replay/{log}', ReplayLogController::class);
        $this->router->post('/api/replay-modified', ReplayModifiedLogController::class);
        $this->router->get('/api/logs/clear', ClearLogsController::class);

        $this->app->route('/socket', new WsServer(new Socket()), ['*'], '');

        foreach ($this->router->getRoutes()->all() as $name => $route) {
            $this->app->routes->add($name, $route);
        }
    }

    protected function detectNextAvailablePort($startPort = 4040): int
    {
        while (is_resource(@fsockopen('127.0.0.1', $startPort))) {
            $startPort++;
        }

        return $startPort;
    }

    public function createHttpServer()
    {
        $dashboardPort = $this->detectNextAvailablePort();

        config()->set('cloakr.dashboard_port', $dashboardPort);

        $this->app = new App('0.0.0.0', $dashboardPort, '0.0.0.0', $this->loop);

        $this->addRoutes();

        return $this;
    }

    public function getApp(): App
    {
        return $this->app;
    }

    public function run()
    {
        $this->loop->run();
    }

    protected function createDatabase(): void
    {
        $databasePath = tempnam(sys_get_temp_dir(), 'cloakr-client-');
        File::put($databasePath, '');

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', $databasePath);

        DB::purge('sqlite'); // Purges the current connection, forcing it to re-bind
        DB::reconnect('sqlite');

        info("Using SQLite database: {$databasePath}", OutputInterface::VERBOSITY_VERBOSE);
    }

    protected function migrateDatabase()
    {
        $this->createDatabase();

        Artisan::call('migrate', [
            '--database' => 'sqlite',
            '--force' => true, // necessary flag to run in PHAR
        ]);

        // After running artisan commands, we need to reset Termwinds output interface.
        Termwind::renderUsing(new ConsoleOutput());

        return $this;
    }
}
