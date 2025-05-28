<?php

namespace Tests\Feature\Client;

use Cloakr\Client\Configuration;
use Cloakr\Client\Factory;
use Cloakr\Client\Logger\LoggedRequest;
use Cloakr\Client\Logger\RequestLogger;
use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;
use React\Http\Browser;
use Tests\Feature\TestCase;


class RequestPluginsTest extends TestCase
{
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
        $paddleRequest = new Request('POST', '/paddle-billing', $this->paddleHeader());

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
        $this->assertEquals([
            "plugin" => "Paddle Billing",
            "uiLabel" => "transaction.completed",
            "cliLabel" => "transaction.completed",
            "details" =>
                [
                    'event_id' => '123456',
                    'notification_id' => '654321',
                ]
        ], $result[0]['plugin_data']);
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

        $this->assertEquals('plugin.error', $result[0]['plugin_data']['cliLabel']);
        $this->assertEquals('plugin.error', $result[0]['plugin_data']['uiLabel']);
        $this->assertArrayHasKey('details', $result[0]['plugin_data']);
        $this->assertStringContainsString('Trying to access array', $result[0]['plugin_data']['details']['Error']);
    }


    /** @test */
    public function it_matches_a_github_actions_request()
    {
        $githubRequest = new Request('POST', '/github-actions', $this->githubActionsHeader());

        $this->logRequest($githubRequest);

        $response = $this->await($this->browser->get('http://127.0.0.1:4040/api/logs'));
        $result = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey('plugin_data', $result[0]);

        $this->assertArrayHasKey('plugin', $result[0]['plugin_data']);
        $this->assertEquals('GitHub', $result[0]['plugin_data']['plugin']);
    }

    /** @test */
    public function it_contains_github_push_data()
    {
        $githubData = [
            "repository" => [
                "full_name" => "beyondcode/cloakr"
            ],
            'ref' => 'refs/heads/main',
            'pusher' => [
                'name' => 'Diana Scharf',
                'email' => 'diana@beyondco.de'
            ],
            'compare' => 'https://cloakr.dev',
            'head_commit' => [
                'message' => 'New features',
                'added' => ['file1', 'file2'],
                'removed' => ['file3'],
                'modified' => ['file4']
            ]
        ];

        $githubHeader = array_merge(
            $this->githubActionsHeader(),
            [
                'x-github-event' => 'push',
            ]
        );

        $githubRequest = new Request('POST', '/github-actions', $githubHeader, json_encode($githubData));

        $this->logRequest($githubRequest);

        $response = $this->await($this->browser->get('http://127.0.0.1:4040/api/logs'));
        $result = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey('plugin_data', $result[0]);

        $this->assertArrayHasKey('plugin', $result[0]['plugin_data']);
        $this->assertEquals([
            "plugin" => "GitHub",
            "uiLabel" => "push",
            "cliLabel" => "push",
            "details" => [
                "Repository" => "beyondcode/cloakr",
                "Branch" => "refs/heads/main",
                "Author" => "Diana Scharf &lt;diana@beyondco.de&gt;",
                "Compare" => "<a href='https://cloakr.dev'>https://cloakr.dev</a>",
                "Commit" => "<span class='font-mono'>New features</span> <br/> (2 files added, 1 files removed, 1 files modified)"
            ]
        ], $result[0]['plugin_data']);
    }

    /** @test */
    public function it_contains_github_issue_data()
    {
        $githubData = [
            "repository" => [
                "full_name" => "beyondcode/cloakr"
            ],
            'issue' => [
                'number' => 42,
                'title' => 'Feature request',
                'user' => [
                    'login' => 'mechelon'
                ],
                'html_url' => 'http://cloakr.dev'
            ],
        ];

        $githubHeader = array_merge(
            $this->githubActionsHeader(),
            [
                'x-github-event' => 'issues',
            ]
        );

        $githubRequest = new Request('POST', '/github-actions', $githubHeader, json_encode($githubData));

        $this->logRequest($githubRequest);

        $response = $this->await($this->browser->get('http://127.0.0.1:4040/api/logs'));
        $result = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey('plugin_data', $result[0]);

        $this->assertArrayHasKey('plugin', $result[0]['plugin_data']);
        $this->assertEquals([
            "plugin" => "GitHub",
            "uiLabel" => "issues",
            "cliLabel" => "issues",
            "details" => [
                "Repository" => "beyondcode/cloakr",
                "Issue" => "#42 <span class='font-mono'>Feature request</span>",
                "Author" => "mechelon",
                "URL" => "<a href='http://cloakr.dev'>http://cloakr.dev</a>",
            ]
        ], $result[0]['plugin_data']);
    }

    /** @test */
    public function it_contains_github_pull_request_data()
    {
        $githubData = [
            "repository" => [
                "full_name" => "beyondcode/cloakr"
            ],
            'pull_request' => [
                'number' => 42,
                'title' => 'Feature request',
                'head' => [
                    'ref' => 'refs/heads/new',
                ],
                'base' => [
                    'ref' => 'refs/heads/main',
                ],
                'user' => [
                    'login' => 'mechelon'
                ],
                'html_url' => 'http://cloakr.dev'
            ],
        ];

        $githubHeader = array_merge(
            $this->githubActionsHeader(),
            [
                'x-github-event' => 'pull_request',
            ]
        );

        $githubRequest = new Request('POST', '/github-actions', $githubHeader, json_encode($githubData));

        $this->logRequest($githubRequest);

        $response = $this->await($this->browser->get('http://127.0.0.1:4040/api/logs'));
        $result = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey('plugin_data', $result[0]);

        $this->assertArrayHasKey('plugin', $result[0]['plugin_data']);
        $this->assertEquals([
            "plugin" => "GitHub",
            "uiLabel" => "pull_request",
            "cliLabel" => "pull_request",
            "details" => [
                "Repository" => "beyondcode/cloakr",
                "Pull Request" => "#42 <span class='font-mono'>Feature request</span>",
                "Author" => "mechelon",
                "Branch" => "<span class='font-mono'>refs/heads/new &rarr; refs/heads/main</span>",
                "URL" => "<a href='http://cloakr.dev'>http://cloakr.dev</a>",
            ]
        ], $result[0]['plugin_data']);
    }

    /** @test */
    public function it_contains_github_ping_data()
    {
        $githubData = [
            "repository" => [
                "full_name" => "beyondcode/cloakr"
            ],
            "hook_id" => 123,
            'hook' => [
                'name' => "Hook",
                'events' => ['push', 'pull_request']
            ],
        ];

        $githubHeader = array_merge(
            $this->githubActionsHeader(),
            [
                'x-github-event' => 'ping',
            ]
        );

        $githubRequest = new Request('POST', '/github-actions', $githubHeader, json_encode($githubData));

        $this->logRequest($githubRequest);

        $response = $this->await($this->browser->get('http://127.0.0.1:4040/api/logs'));
        $result = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey('plugin_data', $result[0]);

        $this->assertArrayHasKey('plugin', $result[0]['plugin_data']);
        $this->assertEquals([
            "plugin" => "GitHub",
            "uiLabel" => "ping",
            "cliLabel" => "ping",
            "details" => [
                "Hook ID" => 123,
                "Hook Name" => "Hook",
                "Repository" => "beyondcode/cloakr",
                "Hook Events" => "<span class='font-mono'>push, pull_request</span>"
            ]
        ], $result[0]['plugin_data']);
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
