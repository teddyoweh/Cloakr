<?php

namespace Cloakr\Client\Logger\Plugins;

use Cloakr\Client\Logger\LoggedRequest;

abstract class BasePlugin
{
    public static function make(LoggedRequest $loggedRequest): self
    {
        return new static($loggedRequest);
    }

    public function __construct(protected LoggedRequest $loggedRequest) {}

    abstract public function getTitle(): string;

    abstract public function matchesRequest(): bool;

    abstract public function getPluginData(): PluginData;
}
