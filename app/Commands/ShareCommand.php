<?php

namespace Cloakr\Client\Commands;


use Cloakr\Client\Factory;
use chillerlan\QRCode\Common\Version;
use chillerlan\QRCode\Data\QRMatrix;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Str;
use React\EventLoop\LoopInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function Cloakr\Common\banner;
use function Cloakr\Common\error;
use function Cloakr\Common\info;
use function Termwind\render;

class ShareCommand extends ServerAwareCommand
{


    protected $signature = 'share {host} {--subdomain=} {--auth=} {--basicAuth=} {--dns=} {--domain=} {--qr} {--qr-code}';

    protected $description = 'Share a local url with a remote cloakr server';

    protected ?bool $isWindows = null;

    public function handle()
    {
        banner();
        $this->ensureEnvironmentSetup();

        info("Cloakr version v" . config('app.version'), options: OutputInterface::VERBOSITY_VERBOSE);

        $auth = $this->option('auth') ?? config('cloakr.auth_token', '');
        info("Using auth token: $auth", options: OutputInterface::VERBOSITY_VERBOSE);

        if (strstr($this->argument('host'), 'host.docker.internal')) {
            config(['cloakr.dns' => true]);
        }

        if ($this->option('dns') !== null) {
            config(['cloakr.dns' => empty($this->option('dns')) ? true : $this->option('dns')]);
        }

        $domain = config('cloakr.default_domain');

        if (!is_null($this->option('server'))) {
            $domain = null;
        }

        if (!is_null($this->option('domain'))) {
            $domain = $this->option('domain');
        }

        if (!is_null($this->option('subdomain'))) {
            $subdomains = explode(',', $this->option('subdomain'));
            info("Trying to use custom subdomain $subdomains[0]", options: OutputInterface::VERBOSITY_VERBOSE);
        } else {
            $host = Str::beforeLast($this->argument('host'), '.');
            $host = str_replace('https://', '', $host);
            $host = str_replace('http://', '', $host);
            $host = Str::beforeLast($host, ':');
            $subdomains = [Str::slug($host)];
            info("Trying to use custom subdomain: $subdomains[0]", options: OutputInterface::VERBOSITY_VERBOSE);
        }

        if ($domain) {
            info("Using custom domain $domain", options: OutputInterface::VERBOSITY_VERBOSE);
        }

        if ($this->option('qr-code') || $this->option('qr')) {

            $qrDomain = $domain ?? $this->getServerHost();
            $subdomain = $subdomains[0];

            $link = "https://$subdomain.$qrDomain";

            render($this->renderQrCode($link));
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

    protected function renderQrCode(string $link)
    {
        $options = new QROptions;

        $options->outputType = QROutputInterface::STRING_TEXT;
        $options->version = Version::AUTO;
        $options->quietzoneSize = 1;
        $options->eol = "\n";
        $options->textLineStart = str_repeat(' ', 2);
        $options->textDark = $this->ansi8('▌', 0);
        $options->textLight = $this->ansi8(' ', 255);
        $options->moduleValues = [
            QRMatrix::M_FINDER_DARK => $this->ansi8('██', 0),
            QRMatrix::M_FINDER => $this->ansi8('░░', 0),
            QRMatrix::M_FINDER_DOT => $this->ansi8('██', 0),
            QRMatrix::M_ALIGNMENT_DARK => $this->ansi8('██', 0),
            QRMatrix::M_ALIGNMENT => $this->ansi8('░░', 0),
            QRMatrix::M_VERSION_DARK => $this->ansi8('██', 0),
            QRMatrix::M_VERSION => $this->ansi8('░░', 0),
        ];

        return (new QRCode($options))->render($link);
    }

    protected function ansi8(string $str, int $color, bool $background = false): string
    {
        $color = max(0, min($color, 255));
        $background = ($background ? 0 : 255);

        return sprintf("\x1b[%s;5;%sm%s\x1b[0m", $background, $color, $str);
    }

    protected function detectOperatingSystem(): void
    {
        $this->isWindows = strpos(php_uname('s'), 'Windows') !== false;
    }

    protected function ensureEnvironmentSetup(): void
    {
        if (!$this->isWindows()) {
            return;
        }
        if (!$this->isWmicAvailable()) {
            error('The "wmic" command is not available on this Windows machine.');
            error(
                'Please refer to the documentation for more information: https://cloakr.dev/docs/troubleshooting',
                abort: true
            );
        }
    }

    protected function isWmicAvailable(): bool
    {
        $output = [];
        $exitCode = 0;

        exec('wmic /?', $output, $exitCode);

        return $exitCode === 0;
    }

    protected function isWindows(): bool
    {
        if ($this->isWindows === null) {
            $this->detectOperatingSystem();
        }

        return $this->isWindows;
    }
}
