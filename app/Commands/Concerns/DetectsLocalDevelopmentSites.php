<?php

namespace Cloakr\Client\Commands\Concerns;

use Symfony\Component\Console\Output\OutputInterface;
use function Cloakr\Common\info;

trait DetectsLocalDevelopmentSites
{
    protected ?string $configPath = null;

    protected ?array $valetConfig = null;

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

    protected function configFilePath(): string
    {
        return $this->configPath . DIRECTORY_SEPARATOR . 'config.json';
    }

    protected function detectProtocol($host): string
    {
        if (file_exists($this->certificateFile($host))) {
            return 'https://';
        }

        return config('cloakr.default_https', false) ? 'https://' : 'http://';
    }

    protected function certificateFile(string $host): string
    {
        return $this->configPath . DIRECTORY_SEPARATOR . 'Certificates' . DIRECTORY_SEPARATOR . $host . '.crt';
    }

    protected function sitesPath(): string
    {
        return $this->configPath . DIRECTORY_SEPARATOR . 'Sites';
    }

    protected function detectSharedSiteNameFromCwd(): string
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

    protected function detectSharedSitePathFromHostname(string $sharedHost): ?string
    {
        if (is_null($this->valetConfig)) {
            info("No Herd/Valet configuration file found - skipping...", options: OutputInterface::VERBOSITY_VERBOSE);
            return null;
        }

        $hostname = parse_url($sharedHost, PHP_URL_HOST);

        $tldParts = explode('.', $hostname);
        $tld = count($tldParts) > 1 ? end($tldParts) : null;

        if ($tld !== $this->detectTld()) {
            return null;
        }

        // Remove TLD from the end
        $siteName = substr($hostname, 0, -strlen($tld) - 1);

        info("Detecting site path for $siteName...", options: OutputInterface::VERBOSITY_VERBOSE);

        $paths = $this->valetConfig['paths'] ?? [];
        $projectPath = null;

        foreach ($paths as $sitePath) {
            if (is_dir($sitePath)) {
                info("Searching in $sitePath...", options: OutputInterface::VERBOSITY_VERBOSE);

                $site = collect(scandir($sitePath))
                    ->filter(function ($site) {
                        return !in_array($site, ['.', '..', '.DS_Store']);
                    })
                    ->map(function ($site) use ($sitePath) {
                        return $sitePath . DIRECTORY_SEPARATOR . $site;
                    })
                    ->mapWithKeys(function ($site) {
                        return [$site => basename($site)];
                    })
                    ->filter(function ($site, $sourcePath) use ($siteName) {
                        return $site === $siteName;
                    })
                    ->keys()
                    ->first();

                if ($site) {
                    $projectPath = realpath($site);
                    info("Found site path $projectPath", options: OutputInterface::VERBOSITY_VERBOSE);
                    break;
                }
            }
        }

        return $projectPath;
    }
}
