<?php

namespace Cloakr\Client\Contracts;

use Cloakr\Client\Logger\LoggedRequest;

interface LoggerContract
{
    public function synchronizeRequest(LoggedRequest $loggedRequest): void;

    public function synchronizeResponse(LoggedRequest $loggedRequest, string $rawResponse): void;
}
