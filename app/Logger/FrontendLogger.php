<?php

namespace Cloakr\Client\Logger;

use Cloakr\Client\Contracts\LoggerContract;
use Cloakr\Client\Http\Resources\LogListResource;
use React\Http\Browser;

class FrontendLogger implements LoggerContract
{

    public function __construct(protected Browser $browser)
    {
    }

    public function synchronizeRequest(LoggedRequest $loggedRequest): void
    {
        $this
            ->browser
            ->post(
                'http://127.0.0.1:'.config()->get('cloakr.dashboard_port').'/api/logs',
                ['Content-Type' => 'application/json'],
                json_encode(LogListResource::fromLoggedRequest($loggedRequest)->toArray(), JSON_INVALID_UTF8_IGNORE)
            );
    }

    public function synchronizeResponse(LoggedRequest $loggedRequest, LoggedResponse $loggedResponse): void
    {
        $this->synchronizeRequest($loggedRequest);
    }
}
