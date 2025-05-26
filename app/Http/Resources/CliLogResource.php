<?php

namespace Cloakr\Client\Http\Resources;

use Cloakr\Client\Logger\LoggedRequest;

class CliLogResource
{

    public function __construct(
        protected string $request_method,
        protected string $request_uri,
        protected int    $duration,
        protected string $time,
        protected string  $color,
        protected string $cliLabel,
        protected ?int   $status_code = null,
    )
    {
    }

    public static function fromLoggedRequest(LoggedRequest $loggedRequest): self
    {
        return new CliLogResource(
            $loggedRequest->getRequest()->getMethod(),
            $loggedRequest->getRequest()->getUriString(),
            $loggedRequest->getDuration(),
            $loggedRequest->getStartTime()->isToday() ? $loggedRequest->getStartTime()->toTimeString() : $loggedRequest->getStartTime()->toDateTimeString(),
            self::getRequestColor($loggedRequest),
            $loggedRequest->getCliLabel(),
            optional($loggedRequest->getResponse())->getStatusCode()
        );
    }

    public function toArray(): array
    {
        return [
            'request_method' => $this->request_method,
            'request_uri' => $this->request_uri,
            'duration' => $this->duration,
            'time' => $this->time,
            'color' => $this->color,
            'status_code' => $this->status_code,
            'cli_label' => $this->cliLabel
        ];
    }


    protected static function getRequestColor(?LoggedRequest $request): string
    {
        $statusCode = optional($request->getResponse())->getStatusCode();
        $color = 'white';

        if ($statusCode >= 200 && $statusCode < 300) {
            $color = 'green';
        } elseif ($statusCode >= 300 && $statusCode < 400) {
            $color = 'blue';
        } elseif ($statusCode >= 400 && $statusCode < 500) {
            $color = 'yellow';
        } elseif ($statusCode >= 500) {
            $color = 'red';
        }

        return $color;
    }
}
