<?php

namespace Tests\Unit;

use Cloakr\Client\Logger\CliLogger;
use Cloakr\Client\Logger\DatabaseLogger;
use Cloakr\Client\Logger\FrontendLogger;
use Cloakr\Client\Logger\RequestLogger;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Str;
use Laminas\Http\Request as LaminasRequest;
use Mockery as m;
use Tests\TestCase;
use GuzzleHttp\Psr7\Message;

class RequestLoggerTest extends TestCase
{

    /** @test */
    public function it_can_log_requests()
    {
        $cliLogger = m::mock(CliLogger::class);
        $cliLogger->shouldReceive('synchronizeRequest')->once();

        $frontendLogger = m::mock(FrontendLogger::class);
        $frontendLogger->shouldReceive('synchronizeRequest')->once();

        $logStorage = new DatabaseLogger();

        $requestString = Message::toString(new Request('GET', '/example'));
        $parsedRequest = LaminasRequest::fromString($requestString);

        $logger = new RequestLogger($cliLogger, $frontendLogger, $logStorage);
        $logger->logRequest($requestString, $parsedRequest);

        $this->assertCount(1, $logger->getData());
    }

    /** @test */
    public function it_can_clear_the_requests()
    {
        $cliLogger = m::mock(CliLogger::class);
        $cliLogger->shouldReceive('synchronizeRequest')->once();

        $frontendLogger = m::mock(FrontendLogger::class);
        $frontendLogger->shouldReceive('synchronizeRequest')->once();

        $logStorage = new DatabaseLogger();

        $requestString = Message::toString(new Request('GET', '/example'));
        $parsedRequest = LaminasRequest::fromString($requestString);

        $logger = new RequestLogger($cliLogger, $frontendLogger, $logStorage);
        $logger->logRequest($requestString, $parsedRequest);

        $logger->clear();

        $this->assertCount(0, $logger->getData());
    }

    /** @test */
    public function it_can_associate_a_response_with_a_request()
    {
        $cliLogger = m::mock(CliLogger::class);
        $cliLogger->shouldReceive('synchronizeRequest')->once();
        $cliLogger->shouldReceive('synchronizeResponse')->once();

        $frontendLogger = m::mock(FrontendLogger::class);
        $frontendLogger->shouldReceive('synchronizeRequest')->once();
        $frontendLogger->shouldReceive('synchronizeResponse')->once();

        $logStorage = new DatabaseLogger();

        $requestString = Message::toString(new Request('GET', '/example', ["x-cloakr-request-id" => Str::uuid()->toString()]));
        $parsedRequest = LaminasRequest::fromString($requestString);

        $logger = new RequestLogger($cliLogger, $frontendLogger, $logStorage);
        $loggedRequest = $logger->logRequest($requestString, $parsedRequest);

        $this->assertNull($logger->findLoggedRequest($loggedRequest->id())->getResponse());

        $responseString = Message::toString(new Response(200, [], 'Hello World!'));

        $logger->logResponse($parsedRequest, $responseString);

        $this->assertNotNull($logger->findLoggedRequest($loggedRequest->id())->getResponse());
    }

    /** @test */
    public function it_can_find_a_request_by_id()
    {
        $cliLogger = m::mock(CliLogger::class);
        $cliLogger->shouldReceive('synchronizeRequest')->once();

        $frontendLogger = m::mock(FrontendLogger::class);
        $frontendLogger->shouldReceive('synchronizeRequest')->once();

        $logStorage = new DatabaseLogger();

        $requestString = Message::toString(new Request('GET', '/example'));
        $parsedRequest = LaminasRequest::fromString($requestString);

        $logger = new RequestLogger($cliLogger, $frontendLogger, $logStorage);

        $loggedRequest = $logger->logRequest($requestString, $parsedRequest);

        $this->assertEquals($loggedRequest->id(), $logger->findLoggedRequest($loggedRequest->id())->id());
        $this->assertEquals($loggedRequest->getRequest()->getUri(), $logger->findLoggedRequest($loggedRequest->id())->getRequest()->getUri());
    }

    /** @test */
    public function it_only_stores_a_limited_amount_of_requests()
    {
        $numberOfRequests = config('cloakr.max_logged_requests') + 1;

        $cliLogger = m::mock(CliLogger::class);
        $cliLogger->shouldReceive('synchronizeRequest')->times($numberOfRequests);

        $frontendLogger = m::mock(FrontendLogger::class);
        $frontendLogger->shouldReceive('synchronizeRequest')->times($numberOfRequests);

        $logStorage = new DatabaseLogger();

        $requestString = Message::toString(new Request('GET', '/example'));
        $parsedRequest = LaminasRequest::fromString($requestString);

        $logger = new RequestLogger($cliLogger, $frontendLogger, $logStorage);

        foreach (range(1, $numberOfRequests) as $i) {
            $logger->logRequest($requestString, $parsedRequest);
        }

        $this->assertCount(config('cloakr.max_logged_requests'), $logger->getData());
    }
}
