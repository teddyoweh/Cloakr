<?php

namespace Cloakr\Client\Logger\Plugins;

class GitHubPlugin extends BasePlugin
{

    protected array $content = [];

    protected string $event;

    const SUPPORTED_EVENTS = ['push', 'pull_request', 'issues', 'ping'];

    public function getTitle(): string
    {
        return 'GitHub';
    }

    public function matchesRequest(): bool
    {
        $request = $this->loggedRequest->getRequest();
        $headers = $request->getHeaders();

        return
            str($request->getHeader('User-Agent')->getFieldValue())->contains("GitHub-Hook") &&
            $headers->has('x-github-event');
    }

    public function getPluginData(): PluginData
    {
        try {
            $this->content = json_decode($this->loggedRequest->getRequest()->getContent(), true);
            $this->detectEventType();

            return PluginData::make()
                ->setPlugin($this->getTitle())
                ->setLabel($this->getEventLabel())
                ->setDetails($this->getEventDetails());
        } catch (\Throwable $e) {
            return PluginData::error($this->getTitle(), $e);
        }
    }

    protected function detectEventType(): void
    {
        $this->event = $this->loggedRequest->getRequest()->getHeader('x-github-event')->getFieldValue();
    }

    protected function getEventLabel(): string
    {
        if (!isset($this->content['action'])) {
            return $this->event;
        }

        return "{$this->event}.{$this->content['action']}";
    }

    protected function getEventDetails(): array
    {
        if (!in_array($this->event, self::SUPPORTED_EVENTS)) {
            return [
                "Unsupported event: $this->event" => "This event is not supported by the plugin yet. Feel free to contribute!"
            ];
        }

        return match ($this->event) {
            'push' => $this->getPushDetails(),
            'issues' => $this->getIssueDetails(),
            'pull_request' => $this->getPullRequestDetails(),
            'ping' => $this->getPingDetails(),
            default => [],
        };
    }

    protected function getPushDetails(): array
    {
        return [
            'Repository' => $this->content['repository']['full_name'],
            'Branch' => $this->content['ref'],
            'Author' => "{$this->content['pusher']['name']} &lt;{$this->content['pusher']['email']}&gt;",
            'Compare' => "<a href='{$this->content['compare']}'>{$this->content['compare']}</a>",
            "Commit" => "<span class='font-mono'>{$this->content['head_commit']['message']}</span> <br/> (" . count($this->content['head_commit']['added']) . " files added, " . count($this->content['head_commit']['removed']) . " files removed, " . count($this->content['head_commit']['modified']) . " files modified)"
        ];
    }

    protected function getIssueDetails(): array
    {
        return [
            'Repository' => $this->content['repository']['full_name'],
            'Issue' => "#{$this->content['issue']['number']} <span class='font-mono'>{$this->content['issue']['title']}</span>",
            'Author' => $this->content['issue']['user']['login'],
            'URL' => "<a href='{$this->content['issue']['html_url']}'>{$this->content['issue']['html_url']}</a>"
        ];
    }

    protected function getPullRequestDetails(): array
    {
        return [
            'Repository' => $this->content['repository']['full_name'],
            'Pull Request' => "#{$this->content['pull_request']['number']} <span class='font-mono'>{$this->content['pull_request']['title']}</span>",
            'Author' => $this->content['pull_request']['user']['login'],
            "Branch" => "<span class='font-mono'>{$this->content['pull_request']['head']['ref']} &rarr; {$this->content['pull_request']['base']['ref']}</span>",
            'URL' => "<a href='{$this->content['pull_request']['html_url']}'>{$this->content['pull_request']['html_url']}</a>"
        ];
    }

    protected function getPingDetails(): array
    {
        return [
            'Hook ID' => $this->content['hook_id'],
            'Hook Name' => $this->content['hook']['name'],
            'Repository' => $this->content['repository']['full_name'],
            'Hook Events' => "<span class='font-mono'>" . implode(', ', $this->content['hook']['events']) . "</span>"
        ];
    }

}
