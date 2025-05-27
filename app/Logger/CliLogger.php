<?php

namespace Cloakr\Client\Logger;

use Cloakr\Client\Contracts\LoggerContract;
use Cloakr\Client\Http\Resources\CliLogResource;
use Cloakr\Client\Support\ConsoleSectionOutput;
use Illuminate\Console\Concerns\InteractsWithIO;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Terminal;
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
    protected $terminalWidth;

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
    protected function getTerminalWidth()
    {
        if ($this->terminalWidth == null) {
            $this->terminalWidth = (new Terminal)->getWidth();

            $this->terminalWidth = $this->terminalWidth >= 30
                ? $this->terminalWidth
                : 30;
        }

        return $this->terminalWidth;
    }

    public function getSection() {
        return new ConsoleSectionOutput($this->output->getStream(), $this->consoleSectionOutputs, $this->output->getVerbosity(), $this->output->isDecorated(), $this->output->getFormatter());
    }

    /**
     * @return ConsoleOutputInterface
     */
    public function getOutput()
    {
        return $this->output;
    }

    public function renderMessageBox(string $text, string $bgColor = "bg-pink-100", $textColor = "text-pink-600", $additionalClasses = "") {
        render("");

        $terminalWidth = terminal()->width();

        if (strlen($text) > $terminalWidth) {
            $lines = collect(explode("\n", wordwrap($text, $terminalWidth, "\n")))
                ->map(function ($line) {
                    return trim($line);
                })
                ->filter();
        }
        else {
            $lines = [$text];
        }

        foreach ($lines as $line) {
            render("<div class='mx-2 w-full px-3 $bgColor $textColor $additionalClasses'> $line </div>");
        }
    }

    public function renderInfo($string) {
        render("<div class='px-2 w-full text-center'> $string </div>");
    }

    public function renderError($text) {
        $this->renderMessageBox($text, "bg-red-100", "text-red-600");
    }

    public function renderConnectionTable($data) {
        render("");

        $template = <<<HTML
    <div class="flex ml-2 mr-6">
        <span class="w-24">key</span>
        <span class=" text-gray-800">&nbsp;</span>
        <span class="text-left font-bold">value</span>
    </div>
HTML;

        foreach ($data as $key => $value) {
            $output = str_replace(
                ['key', 'value'],
                [$key, $value],
                $template
            );

            render($output);
        }
    }


    public function synchronizeRequest(LoggedRequest $loggedRequest): void
    {
        $cliLog = CliLogResource::fromLoggedRequest($loggedRequest);

        if ($this->requests->has($loggedRequest->id())) {
            $this->requests[$loggedRequest->id()] = $cliLog;
        } else {
            $this->requests->prepend($cliLog, $loggedRequest->id());
        }
        $this->requests = $this->requests->slice(0, config('cloakr.max_logged_requests', 100));

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
}
