<?php

namespace Cloakr\Client\Http\Resources;

use Cloakr\Client\Logger\LoggedRequest;
use Cloakr\Client\RequestLog;

class LogListResource
{

    public function __construct(
        protected string $id,
        protected int    $duration,
        protected string $request_method,
        protected string $request_uri,
        protected ?array  $plugin_data = null,
        protected ?int   $status_code = null
    )
    {
    }


    public static function fromRequestLog(RequestLog $requestLog): self
    {
        return new LogListResource(
            $requestLog->request_id,
            $requestLog->duration,
            $requestLog->request_method,
            $requestLog->request_uri,
            $requestLog->plugin_data ? json_decode($requestLog->plugin_data, true) : null,
            $requestLog->response->status_code ?? null
        );
    }

    public static function fromLoggedRequest(LoggedRequest $loggedRequest): self
    {
        return new LogListResource(
            $loggedRequest->id(),
            $loggedRequest->getDuration(),
            $loggedRequest->getRequest()->getMethod(),
            $loggedRequest->getRequest()->getUriString(),
            $loggedRequest->getPluginData(),
            $loggedRequest->getResponse()?->getStatusCode()
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'duration' => $this->duration,
            'request_method' => $this->request_method,
            'request_uri' => $this->request_uri,
            'plugin_data' => $this->plugin_data,
            'status_code' => $this->status_code
        ];
    }
}
