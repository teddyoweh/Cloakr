<?php

namespace Cloakr\Client\Traits;

use Cloakr\Client\Commands\Support\CloakrToken;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

trait FetchesPlatformData
{
    protected function isProToken(): bool
    {
        return $this->cloakrToken()->isPro();
    }

    protected function isValidToken(): bool
    {
        return $this->cloakrToken()->isValid();
    }

    protected function cloakrToken(): CloakrToken
    {
        /* With the array driver, Laravel Zero holds the cache for one whole CLI execution,
         * which is very handy. */
        return Cache::rememberForever('cloakr_token_' . $this->getToken(), function () {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])
                ->post("{$this->platformEndpoint()}client/token", [
                    'token' => $this->getToken(),
                ]);

            if (!$response->ok()) {
                return CloakrToken::invalid($this->getToken())
                    ->setError('Could not validate token. HTTP ' . $response->status());
            }

            $data = $response->json('data');

            if (!isset($data['is_valid'], $data['is_pro'])) {
                return CloakrToken::invalid($this->getToken());
            }

            return match ([$data['is_valid'], $data['is_pro']]) {
                [true, true] => CloakrToken::pro($this->getToken()),
                [true, false] => CloakrToken::valid($this->getToken()),
                default => CloakrToken::invalid($this->getToken()),
            };
        });
    }

    protected function getClosestServer(): array
    {
        $closestServer = [];

        $network = $this->fetchServerNetwork();

        if (array_key_exists('closest_server', $network)) {
            $closestServer = $network['closest_server'];
        }

        return $closestServer;
    }

    protected function getServers(): Collection
    {
        $servers = collect();
        $network = $this->fetchServerNetwork();

        if (array_key_exists('servers', $network)) {
            $servers = collect($network['servers'])->sort();
        }

        return $servers;
    }

    protected function fetchServerNetwork(): array
    {
        return Cache::rememberForever('server_network', function () {
            $response = Http::post($this->platformEndpoint() . 'client/closest-server', [
                'token' => $this->getToken()
            ]);

            if (!$response->ok()) {
                return [];
            }

            $result = $response->json();

            if (!$result) {
                return [];
            }

            return $result;
        });
    }

    protected function getTeamDomains(): Collection
    {
        return Cache::rememberForever('team_domains', function () {
            $domains = collect();

            $response = Http::post($this->platformEndpoint() . 'client/team-domains', [
                'token' => $this->getToken()
            ]);

            if (!$response->ok()) {
                return $domains;
            }

            $result = $response->json();

            if (!$result) {
                return $domains;
            }

            if (array_key_exists('domains', $result)) {
                $domains = collect($result['domains'])->sort();
            }

            return $domains;
        });
    }

    protected function hasTeamDomains(): bool
    {
        return $this->getTeamDomains()->isNotEmpty();
    }

    protected function platformEndpoint(): string
    {
        return config('cloakr.platform_url', 'https://cloakr.dev') . '/api/';
    }

    protected function cloakrPlatformSetup()
    {
        return config('cloakr.platform_url', 'https://cloakr.dev') !== null && config('cloakr.platform_url') !== "";
    }
}
