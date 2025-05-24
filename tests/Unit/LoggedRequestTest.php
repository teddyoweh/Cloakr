<?php

namespace Tests\Unit;

use Cloakr\Client\Logger\LoggedRequest;
use GuzzleHttp\Psr7\Request;
use Laminas\Http\Request as LaminasRequest;
use Tests\TestCase;
use GuzzleHttp\Psr7\Message;

class LoggedRequestTest extends TestCase
{
    /** @test */
    public function it_retrieves_the_request_id()
    {
        $rawRequest = Message::toString(new Request('GET', '/cloakr', [
            'X-Cloakr-Request-ID' => 'example-request',
        ]));
        $parsedRequest = LaminasRequest::fromString($rawRequest);

        $loggedRequest = new LoggedRequest($rawRequest, $parsedRequest);
        $this->assertSame('example-request', $loggedRequest->id());
    }

    /** @test */
    public function it_retrieves_the_request_for_chrome_extensions()
    {
        $rawRequest = Message::toString(new Request('GET', '/cloakr', [
            'Origin' => 'chrome-extension://cloakr',
            'X-Cloakr-Request-ID' => 'example-request',
        ]));
        $parsedRequest = LaminasRequest::fromString($rawRequest);

        $loggedRequest = new LoggedRequest($rawRequest, $parsedRequest);
        $this->assertSame('example-request', $loggedRequest->id());
    }

    /** @test */
    public function it_returns_post_data_for_json_payloads()
    {
        $postData = [
            'name' => 'Marcel',
            'project' => 'cloakr',
        ];

        $rawRequest = Message::toString(new Request('GET', '/cloakr', [
            'Content-Type' => 'application/json',
        ], json_encode($postData)));
        $parsedRequest = LaminasRequest::fromString($rawRequest);

        $loggedRequest = new LoggedRequest($rawRequest, $parsedRequest);

        $this->assertSame([
            [
                'name' => 'name',
                'value' => 'Marcel',
            ],
            [
                'name' => 'project',
                'value' => 'cloakr',
            ],
        ], $loggedRequest->getPostData());
    }

    /** @test */
    public function it_returns_the_raw_request()
    {
        $rawRequest = Message::toString(new Request('GET', '/cloakr', [
            'X-Cloakr-Request-ID' => 'example-request',
        ]));
        $parsedRequest = LaminasRequest::fromString($rawRequest);

        $loggedRequest = new LoggedRequest($rawRequest, $parsedRequest);
        $this->assertSame($rawRequest, $loggedRequest->getRequestData());
    }

    /** @test */
    public function it_returns_the_parsed_request()
    {
        $rawRequest = Message::toString(new Request('GET', '/cloakr', [
            'X-Cloakr-Request-ID' => 'example-request',
        ]));
        $parsedRequest = LaminasRequest::fromString($rawRequest);

        $loggedRequest = new LoggedRequest($rawRequest, $parsedRequest);
        $this->assertSame($parsedRequest, $loggedRequest->getRequest());
    }
}
