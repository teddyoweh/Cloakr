<?php

namespace Cloakr\Client\Commands;

use Illuminate\Support\Arr;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

use function Cloakr\Common\info;

class ShareCurrentWorkingDirectoryCommand extends ShareCommand
{
    protected $signature = 'share-cwd {host?} {--subdomain=} {--auth=} {--basicAuth=} {--dns=} {--domain=} {--qr} {--qr-code}';

    protected ?string $configPath = null;

    protected ?array $valetConfig = null;

    public function handle()
    {
        $this->loadConfigurationFiles();

        $yaml = $this->loadCloakrYaml();

        $folderName = $this->detectName();

        $host = Arr::get($yaml, 'local-url', $this->prepareSharedHost($folderName . '.' . $this->detectTld()));

        $this->input->setArgument('host', $host);

        $subdomain = Arr::get($yaml, 'subdomain', $this->option('subdomain'));

        if (!$subdomain) {
            $this->input->setOption('subdomain', str_replace('.', '-', $folderName));
        } else {
            $this->input->setOption('subdomain', $subdomain);
        }

        $this->input->setOption('domain', Arr::get($yaml, 'custom-domain', $this->option('domain')));
        $this->input->setOption('server', Arr::get($yaml, 'cloakr-server', $this->option('server')));

        $authString = "";
        $username = Arr::get($yaml, "auth.username");
        $password = Arr::get($yaml, "auth.password");

        if ($username && $password) {
            $authString = $username . ":" . $password;
        }

        $authString = empty($authString) ? $this->option('basicAuth') : $authString;

        $this->input->setOption('basicAuth', $authString);

        parent::handle();
    }

    protected function loadCloakrYaml(): array
    {
        try {
            return Yaml::parseFile(getcwd() . DIRECTORY_SEPARATOR . 'cloakr.yml');
        } catch (\Exception $e) {
            return [];
        }
    }

    protected function loadConfigurationFiles(): void
    {
        $this->configPath = $this->getHerdValetConfigFilePath();

        if ($this->configPath === null) {
            $this->configPath = ($_SERVER['HOME'] ?? $_SERVER['USERPROFILE']) . DIRECTORY_SEPARATOR . '.config' . DIRECTORY_SEPARATOR . 'valet';
        }

        info("Detected Valet config path: " . $this->configPath, options: OutputInterface::VERBOSITY_VERBOSE);

        if (file_exists($this->configFilePath())) {
            $this->valetConfig = json_decode(file_get_contents($this->configFilePath()), true);

            info("Trying to read Valet config file...", options: OutputInterface::VERBOSITY_VERBOSE);
            info("JSON errors: " . json_last_error_msg(), options: OutputInterface::VERBOSITY_VERBOSE);
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

        info("Detecting name for $projectPath...", options: OutputInterface::VERBOSITY_VERBOSE);
        info("Checking sites at " . $this->sitesPath(), options: OutputInterface::VERBOSITY_VERBOSE);

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
                info("Found linked site $site", options: OutputInterface::VERBOSITY_VERBOSE);

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
