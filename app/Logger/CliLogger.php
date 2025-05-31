<?php

namespace Cloakr\Client\Logger;

use Cloakr\Client\Contracts\LoggerContract;
use Cloakr\Client\Http\Resources\CliLogResource;
use Cloakr\Client\Support\ConsoleSectionOutput;
use Illuminate\Console\Concerns\InteractsWithIO;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use function Termwind\parse;
use function Termwind\render;
use function Termwind\terminal;

class CliLogger implements LoggerContract
{
    use InteractsWithIO;

    /** @var ConsoleOutputInterface */
    protected $output;

    /** @var Collection */
    protected $requests;

    protected $section;

    protected $verbColors = [
        'GET' => 'blue',
        'HEAD' => '#6C7280',
        'OPTIONS' => '#6C7280',
        'POST' => 'yellow',
        'PUT' => 'yellow',
        'PATCH' => 'yellow',
        'DELETE' => 'red',
    ];

    protected $consoleSectionOutputs = [];

    /**
     * The current terminal width.
     *
     * @var int|null
     */
    protected ?int $terminalWidth = null;

    /**
     * The current terminal height.
     *
     * @var int|null
     */
    protected ?int $terminalHeight = null;

    protected int $headerLineCount = 10;

    protected int $maximumRequests = 50;

    public function __construct(ConsoleOutputInterface $consoleOutput)
    {
        $this->output = $consoleOutput;

        $this->section = $this->getSection();
        $this->requests = new Collection();
    }

    /**
     * Computes the terminal width.
     *
     * @return int
     */
    protected function getTerminalWidth(): int
    {
        if ($this->terminalWidth == null) {
            $this->terminalWidth = terminal()->width();

            $this->terminalWidth = $this->terminalWidth >= 30
                ? $this->terminalWidth
                : 30;
        }

        return $this->terminalWidth;
    }

    /**
     * Computes the terminal height.
     *
     * @return int
     */
    protected function getTerminalHeight(): int
    {
        if ($this->terminalHeight == null) {
            $this->terminalHeight = terminal()->height();

            $this->terminalHeight = $this->terminalHeight >= 50
                ? $this->terminalHeight
                : 50;
        }

        return $this->terminalHeight;
    }

    public function getSection(): ConsoleSectionOutput {
        return new ConsoleSectionOutput($this->output->getStream(), $this->consoleSectionOutputs, $this->output->getVerbosity(), $this->output->isDecorated(), $this->output->getFormatter());
    }

    /**
     * @return ConsoleOutputInterface
     */
    public function getOutput(): ConsoleOutputInterface
    {
        return $this->output;
    }

    public function renderMessage(string $message): void {
        render("");

        $this->headerLineCount += $this->countOutputLines($message) + 1;

        $this->line($message);
    }

    public function renderError($text): void {
        render("<div class='mx-2 w-full px-3 bg-red-100 text-red-600'> $text </div>");
    }

    public function renderConnectionTable($data): void {


        $template = <<<HTML
    <div class="flex ml-2 mr-6">
        <span class="w-24">key</span>
        <span class=" text-gray-800">&nbsp;</span>
        <span class="text-left font-bold">value</span>
    </div>
HTML;

        $tableOutput = '';
        foreach ($data as $key => $value) {
            $output = str_replace(
                ['key', 'value'],
                [$key, $value],
                $template
            );

            $tableOutput = $tableOutput . PHP_EOL . (parse($output));
        }

        $this->headerLineCount += $this->countOutputLines($tableOutput);

        $this->line($tableOutput);
    }


    public function synchronizeRequest(LoggedRequest $loggedRequest): void
    {
        $this->getMaximumRequests();

        $cliLog = CliLogResource::fromLoggedRequest($loggedRequest);

        if ($this->requests->has($loggedRequest->id())) {
            $this->requests[$loggedRequest->id()] = $cliLog;
        } else {
            $this->requests->prepend($cliLog, $loggedRequest->id());
        }

        $this->requests = $this->requests->slice(0, $this->maximumRequests);

        $terminalWidth = $this->getTerminalWidth();

        $requests = $this->requests->map(function (CliLogResource $cliLog) {
            return $cliLog->toArray();
        });

        $maxMethod = mb_strlen($requests->max('request_method'));
        $maxDuration = mb_strlen($requests->max('duration'));

        $output = $requests->map(function ($loggedRequest) use ($terminalWidth, $maxMethod, $maxDuration) {
            $method = $loggedRequest['request_method'];
            $spaces = str_repeat(' ', max($maxMethod + 2 - mb_strlen($method), 0));
            $url = $loggedRequest['request_uri'];
            $duration = $loggedRequest['duration'];
            $time = $loggedRequest['time'];
            $durationSpaces = str_repeat(' ', max($maxDuration + 2 - mb_strlen($duration), 0));
            $color = $loggedRequest['color'];
            $status = $loggedRequest['status_code'];
            $cliLabel = $loggedRequest['cli_label'];


            $dots = str_repeat('.', max($terminalWidth - strlen($method.$spaces.$cliLabel.$url.$time.$durationSpaces.$duration) - 20, 0));

            if (empty($dots)) {
                $url = substr($url, 0, $terminalWidth - strlen($method.$spaces.$cliLabel.$time.$durationSpaces.$duration) - 20 - 3).'...';
            } else {
                $dots .= ' ';
            }

            return sprintf(
                '  <fg=%s;options=bold>%s </>   <fg=%s;options=bold>%s%s</><options=bold>%s</> <fg=#6C7280>%s</> %s <fg=#6C7280>%s%s%s ms</>',
                $color,
                $status,
                $this->verbColors[$method] ?? 'default',
                $method,
                $spaces,
                $url,
                $dots,
                $cliLabel,
                $time,
                $durationSpaces,
                $duration,
            );
        });

        $this->section->overwrite($output);
    }

    public function synchronizeResponse(LoggedRequest $loggedRequest, LoggedResponse $loggedResponse): void
    {
        $this->synchronizeRequest($loggedRequest);
    }

    protected function countOutputLines($output): int {
        return count(explode(PHP_EOL, $output));
    }

    protected function getMaximumRequests(): void {
        $this->maximumRequests = $this->getTerminalHeight() - $this->headerLineCount - 1;
    }
}
