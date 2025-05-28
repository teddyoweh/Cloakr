<?php

namespace Tests\Feature\Client;

use Cloakr\Client\Configuration;
use Cloakr\Client\Factory;
use Cloakr\Client\Http\HttpClient;
use Cloakr\Client\Logger\LoggedRequest;
use Cloakr\Client\Logger\RequestLogger;
use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Arr;
use Mockery as m;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use React\Http\Browser;
use React\Http\Message\ResponseException;
use Tests\Feature\TestCase;


class RequestPluginsTest extends TestCase
{

//    use InteractsWithDashboard; // TODO: ?

    protected Browser $browser;
    protected Factory $dashboardFactory;
    protected RequestLogger $requestLogger;

    public function setUp(): void
    {
        parent::setUp();

        $this->browser = new Browser($this->loop);
        $this->requestLogger = $this->app->make(RequestLogger::class);
        $this->startDashboard();
    }


    public function tearDown(): void
    {
        parent::tearDown();

        $this->dashboardFactory->getApp()->close();
    }


    protected function logRequest(RequestInterface $request): LoggedRequest
    {
        return $this->requestLogger->logRequest(
            Message::toString($request),
            \Laminas\Http\Request::fromString(Message::toString($request))
        );
    }

    /** @test */
    public function it_matches_a_paddle_request()
    {
        $paddleRequest = new Request('GET', '/paddle-billing', $this->paddleHeader());

        $this->logRequest($paddleRequest);

        $response = $this->await($this->browser->get('http://127.0.0.1:4040/api/logs'));
        $result = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey('plugin_data', $result[0]);

        $this->assertArrayHasKey('plugin', $result[0]['plugin_data']);
        $this->assertEquals('Paddle Billing', $result[0]['plugin_data']['plugin']);
    }

    /** @test */
    public function it_contains_paddle_event_data()
    {
        $paddleData = [
            "event_type" => "transaction.completed",
            "event_id" => "123456",
            "notification_id" => "654321",
        ];

        $paddleRequest = new Request('POST', '/paddle-billing', $this->paddleHeader(), json_encode($paddleData));

        $this->logRequest($paddleRequest);

        $response = $this->await($this->browser->get('http://127.0.0.1:4040/api/logs'));
        $result = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey('plugin_data', $result[0]);

        $this->assertArrayHasKey('plugin', $result[0]['plugin_data']);
        $this->assertEquals($result[0]['plugin_data'], [
            "plugin" => "Paddle Billing",
            "uiLabel" => "transaction.completed",
            "cliLabel" => "transaction.completed",
            "details" =>
                [
                    'event_id' => '123456',
                    'notification_id' => '654321',
                ]
        ]);
    }

    /** @test */
    public function it_returns_plugin_error_on_missing_data()
    {
        $paddleRequest = new Request('POST', '/paddle-billing', $this->paddleHeader());

        $this->logRequest($paddleRequest);

        $response = $this->await($this->browser->get('http://127.0.0.1:4040/api/logs'));
        $result = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey('plugin_data', $result[0]);
        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey('plugin_data', $result[0]);

        $this->assertEquals($result[0]['plugin_data'], [
            "plugin" => "Paddle Billing",
            "uiLabel" => "plugin.error",
            "cliLabel" => "plugin.error",
            "details" => [
                "Error" => "Trying to access array offset on null",
                "File" => "/Users/dianascharf/Dev/cloakr-v3/app/Logger/Plugins/PaddleBillingPlugin.php", // TODO:
                "Line" => 32,
            ],
        ]);
    }


    /** @test */
    public function it_matches_a_github_actions_request()
    {
        $githubRequest = new Request('GET', '/github-actions', $this->githubActionsHeader());

        $this->logRequest($githubRequest);

        $response = $this->await($this->browser->get('http://127.0.0.1:4040/api/logs'));
        $result = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey('plugin_data', $result[0]);

        $this->assertArrayHasKey('plugin', $result[0]['plugin_data']);
        $this->assertEquals('GitHub', $result[0]['plugin_data']['plugin']);
    }

    /** @test */
    public function it_returns_empty_plugin_data_for_unmatched_request()
    {
        $this->logRequest(new Request('GET', '/endpoint'));

        $response = $this->await($this->browser->get('http://127.0.0.1:4040/api/logs'));
        $result = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey('plugin_data', $result[0]);
        $this->assertEmpty($result[0]['plugin_data']);
    }

    // TODO: GH actions

    protected function startDashboard()
    {
        app()->singleton(Configuration::class, function ($app) {
            return new Configuration('localhost', '8080', false);
        });

        $this->dashboardFactory = (new Factory())
            ->setLoop($this->loop)
            ->createHttpServer();
    }

    protected function paddleHeader(): array
    {
        return [
            'User-Agent' => 'Paddle',
            'paddle-signature' => 'foo',
            'paddle-version' => 'bar',
        ];
    }

    protected function githubActionsHeader(): array
    {
        return [
            'User-Agent' => 'GitHub-Hook',
            'x-github-event' => 'push',
        ];
    }

}
