<?php

namespace Cloakr\Client\Contracts;

use Cloakr\Client\Logger\LoggedRequest;
use Cloakr\Client\Logger\LoggedResponse;

interface LoggerContract
{
    public function synchronizeRequest(LoggedRequest $loggedRequest): void;

    public function synchronizeResponse(LoggedRequest $loggedRequest, LoggedResponse $loggedResponse): void;
}
