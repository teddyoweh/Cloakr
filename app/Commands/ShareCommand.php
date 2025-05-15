<?php

namespace App\Commands;

use App\Client\Factory;
use Illuminate\Support\Str;
use React\EventLoop\LoopInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function Termwind\render;

class ShareCommand extends ServerAwareCommand
{
    protected $signature = 'share {host} {--subdomain=} {--auth=} {--basicAuth=} {--dns=} {--domain=}';

    protected $description = 'Share a local url with a remote cloakr server';

    public function handle()
    {
        render('<div class="ml-2 text-pink-500 font-bold"><span class="pr-0.5">></span> Cloakr</div>');

        $auth = $this->option('auth') ?? config('cloakr.auth_token', '');
        render('<div class="ml-3">Using auth token: '.$auth . '</div>', OutputInterface::VERBOSITY_DEBUG);

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

        if (! is_null($this->option('subdomain'))) {
            $subdomains = explode(',', $this->option('subdomain'));
            render('<div class="ml-3">Trying to use custom subdomain: '.$subdomains[0].PHP_EOL . '</div>', OutputInterface::VERBOSITY_VERBOSE);
        } else {
            $host = Str::beforeLast($this->argument('host'), '.');
            $host = str_replace('https://', '', $host);
            $host = str_replace('http://', '', $host);
            $host = Str::beforeLast($host, ':');
            $subdomains = [Str::slug($host)];
            render('<div class="ml-3">Trying to use custom subdomain: '.$subdomains[0].PHP_EOL . '</div>', OutputInterface::VERBOSITY_VERBOSE);
        }

        (new Factory())
            ->setLoop(app(LoopInterface::class))
            ->setHost($this->getServerHost())
            ->setPort($this->getServerPort())
            ->setAuth($auth)
            ->setBasicAuth($this->option('basicAuth'))
            ->createClient()
            ->share(
                $this->argument('host'),
                $subdomains,
                $domain
            )
            ->createHttpServer()
            ->run();
    }
}
