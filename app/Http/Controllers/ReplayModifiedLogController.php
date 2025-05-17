<?php

namespace Cloakr\Client\Http\Controllers;

use Cloakr\Client\Http\HttpClient;
use Cloakr\Client\Logger\LoggedRequest;
use Cloakr\Common\Http\Controllers\Controller;
use Cloakr\Client\Logger\RequestLogger;
use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use Ratchet\ConnectionInterface;
use Laminas\Http\Request as LaminasRequest;

class ReplayModifiedLogController extends Controller
{
    /** @var RequestLogger */
    protected $requestLogger;

    /** @var HttpClient */
    protected $httpClient;

    public function __construct(RequestLogger $requestLogger, HttpClient $httpClient)
    {
        $this->requestLogger = $requestLogger;
        $this->httpClient = $httpClient;
    }

    public function handle(Request $request, ConnectionInterface $httpConnection)
    {

        $rawRequest = $this->buildRawRequest($request);
        $parsedRequest = LaminasRequest::fromString($rawRequest);
        $loggedRequest = new LoggedRequest($rawRequest, $parsedRequest);

        if (is_null($loggedRequest)) {
            $httpConnection->send(Message::toString(new Response(404)));

            return;
        }

        $loggedRequest->refreshId();

        $this->httpClient->performRequest($loggedRequest->getRequest()->toString());

        $httpConnection->send(Message::toString(new Response(200)));
    }

    protected function buildRawRequest(Request $request): string {

        $requestData = $request->all();

        $method = $requestData['method'];
        $uri = $requestData['uri'];
        $headers = $requestData['headers'];
        $body = $requestData['body'];

        $rawRequest = "$method $uri HTTP/1.1\r\n";
        foreach ($headers as $key => $value) {
            $rawRequest .= "$key: $value\r\n";
        }
        $rawRequest .= "\r\n";
        $rawRequest .= $body;

        return $rawRequest;
    }
}
