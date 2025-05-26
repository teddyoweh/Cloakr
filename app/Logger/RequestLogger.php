<?php

namespace Cloakr\Client\Logger;

use Cloakr\Client\Contracts\LogStorageContract;
use Laminas\Http\Request;
use Laminas\Http\Response;

class RequestLogger
{
    public function __construct(protected CliLogger $cliLogger, protected FrontendLogger $frontendLogger, protected LogStorageContract $logStorage, )
    {
    }

    public function findLoggedRequest(string $id): ?LoggedRequest
    {
        return $this->logStorage->requests()->withResponses()->find($id);
    }

    public function logRequest(string $rawRequest, Request $request): LoggedRequest
    {
        $loggedRequest = new LoggedRequest($rawRequest, $request);

        $this->cliLogger->synchronizeRequest($loggedRequest);
        $this->logStorage->synchronizeRequest($loggedRequest);
        $this->frontendLogger->synchronizeRequest($loggedRequest);

        return $loggedRequest;
    }

    public function logResponse(Request $request, string $rawResponse)
    {
        $requests = $this->logStorage->requests()->get();

        $cloakrRequestId = $request->getHeader("x-cloakr-request-id") ? $request->getHeader("x-cloakr-request-id")->getFieldValue() : null;

        if (!$cloakrRequestId) {
            return;
        }

        $loggedRequest = collect($requests)->filter(function (LoggedRequest $loggedRequest) use ($cloakrRequestId) {
            return $loggedRequest->id() === $cloakrRequestId;
        })->first();

        $loggedRequest->setResponse($rawResponse, Response::fromString($rawResponse));
        $loggedRequest->setStopTime();

        $this->logStorage->synchronizeResponse($loggedRequest, $rawResponse);

        $this->frontendLogger->synchronizeResponse($loggedRequest, $rawResponse);

        $this->cliLogger->synchronizeResponse($loggedRequest, $rawResponse);
    }

    public function getData(): array
    {
        $requests = $this->logStorage->requests()->get();

        return $requests ? $requests->toArray() : [];
    }

    public function clear()
    {
        $this->logStorage->requests()->delete();
    }
}
