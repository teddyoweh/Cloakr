<?php

namespace Cloakr\Client\Commands;

use Symfony\Component\Console\Output\OutputInterface;
use function Cloakr\Common\info;

class ShareCurrentWorkingDirectoryCommand extends ShareCommand
{
    protected $signature = 'share-cwd {host?} {--subdomain=} {--auth=} {--basicAuth=} {--dns=} {--domain=} {--qr} {--qr-code}';

    protected ?string $configPath = null;

    protected ?array $valetConfig = null;

    public function handle()
    {
        $this->loadConfigurationFiles();

        $folderName = $this->detectName();

        $host = $this->prepareSharedHost($folderName . '.' . $this->detectTld());

        $this->input->setArgument('host', $host);

        if (!$this->option('subdomain')) {
            $this->input->setOption('subdomain', str_replace('.', '-', $folderName));
        }

        parent::handle();
    }

    protected function loadConfigurationFiles(): void
    {
        $this->configPath = $this->getHerdValetConfigFilePath();

        if ($this->configPath === null) {
            $this->configPath = ($_SERVER['HOME'] ?? $_SERVER['USERPROFILE']) . DIRECTORY_SEPARATOR . '.config' . DIRECTORY_SEPARATOR . 'valet';
        }

        info("Detected Valet config path: " . $this->configPath, OutputInterface::VERBOSITY_VERBOSE);

        if (file_exists($this->configFilePath())) {
            $this->valetConfig = json_decode(file_get_contents($this->configFilePath()), true);

            info("Trying to read Valet config file...", OutputInterface::VERBOSITY_VERBOSE);
            info("JSON errors: " . json_last_error_msg(), OutputInterface::VERBOSITY_VERBOSE);
        }
    }

    protected function getHerdValetConfigFilePath(): ?string
    {
        $configPath = null;

        if (array_key_exists('HOME', $_SERVER)) {
            if ($this->isWindows()) {
                $configPath = $_SERVER['HOME'] . '\.config\herd\config\valet';
            } else {
                $configPath = $_SERVER['HOME'] . '/Library/Application Support/Herd/config/valet';
            }
        } else if (array_key_exists('HOMEPATH', $_SERVER)) {
            $configPath = $_SERVER['HOMEPATH'] . '\.config\herd\config\valet';
        }

        if (!file_exists($configPath)) {
            $configPath = null;
        }

        return $configPath;
    }

    protected function detectTld(): string
    {
        if ($this->valetConfig && array_key_exists('tld', $this->valetConfig)) {
            return $this->valetConfig['tld'];
        }

        return config('cloakr.default_tld', 'test');
    }

    protected function detectName(): string
    {
        $projectPath = getcwd();

        info("Detecting name for $projectPath...", OutputInterface::VERBOSITY_VERBOSE);
        info("Checking sites at " . $this->sitesPath(), OutputInterface::VERBOSITY_VERBOSE);

        if (is_dir($this->sitesPath())) {
            $site = collect(scandir($this->sitesPath()))
                ->filter(function ($site) {
                    return !in_array($site, ['.', '..', '.DS_Store']);
                })
                ->map(function ($site) {
                    return $this->sitesPath() . DIRECTORY_SEPARATOR . $site;
                })->mapWithKeys(function ($site) {
                    return [$site => readlink($site)];
                })->filter(function ($sourcePath) use ($projectPath) {
                    return $sourcePath === $projectPath;
                })
                ->keys()
                ->first();

            if ($site) {
                $projectPath = $site;
                info("Found linked site $site", OutputInterface::VERBOSITY_VERBOSE);

            }
        }

        return basename($projectPath);
    }

    protected function detectProtocol($host): string
    {
        if (file_exists($this->certificateFile($host))) {
            return 'https://';
        }

        return config('cloakr.default_https', false) ? 'https://' : 'http://';
    }

    protected function prepareSharedHost($host): string
    {
        return $this->detectProtocol($host) . $host;
    }

    protected function configFilePath(): string
    {
        return $this->configPath . DIRECTORY_SEPARATOR . 'config.json';
    }

    protected function certificateFile(string $host): string
    {
        return $this->configPath . DIRECTORY_SEPARATOR . 'Certificates' . DIRECTORY_SEPARATOR . $host . '.crt';
    }

    protected function sitesPath(): string
    {
        return $this->configPath . DIRECTORY_SEPARATOR . 'Sites';
    }
}
