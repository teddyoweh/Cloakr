<?php

namespace Cloakr\Client\Logger;

use Cloakr\Client\Logger\Plugins\PluginData;
use Carbon\Carbon;
use Exception;
use Cloakr\Client\Logger\Plugins\PluginManager;
use GuzzleHttp\Psr7\Message;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Laminas\Http\Header\GenericHeader;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Namshi\Cuzzle\Formatter\CurlFormatter;
use Riverline\MultiPartParser\StreamedPart;

class LoggedRequest implements \JsonSerializable
{
    /** @var string */
    protected $rawRequest;

    /** @var Request */
    public $parsedRequest;

    /** @var LoggedResponse */
    protected $response;

    /** @var string */
    protected $id;

    /** @var Carbon */
    protected $startTime;

    /** @var Carbon */
    protected $stopTime;

    /** @var string */
    protected $subdomain;

    protected ?PluginData $pluginData = null;

    public function __construct(string $rawRequest, Request $parsedRequest)
    {
        $this->startTime = now();
        $this->rawRequest = $rawRequest;
        $this->parsedRequest = $parsedRequest;
        $this->id = $this->getRequestId();

        $this->pluginData = app(PluginManager::class)->loadPluginData($this);
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        $data = [
            'id' => $this->id,
            'performed_at' => $this->startTime->toDateTimeString(),
            'duration' => $this->getDuration(),
            'subdomain' => $this->detectSubdomain(),
            'request' => [
                'raw' => $this->isBinary($this->rawRequest) ? 'BINARY' : $this->rawRequest,
                'method' => $this->parsedRequest->getMethod(),
                'uri' => $this->parsedRequest->getUriString(),
                'headers' => $this->parsedRequest->getHeaders()->toArray(),
                'body' => $this->isBinary($this->rawRequest) ? 'BINARY' : $this->parsedRequest->getContent(),
                'query' => $this->parsedRequest->getQuery()->toArray(),
                'post' => $this->getPostData(),
                'curl' => $this->getRequestAsCurl(),
                'plugin' => $this->pluginData ? $this->pluginData->toArray() : null
            ],
        ];

        if ($this->response) {
            $data['response'] = $this->response->toArray();
        }

        return $data;
    }

    public function toDatabase(): array {

        return [
            'request_id' => $this->id,
            'subdomain' => $this->detectSubdomain(),
            'raw_request' => $this->isBinary($this->rawRequest) ? 'BINARY' : $this->rawRequest,
            'start_time' => $this->startTime->toDateTimeString(), // TODO: milliseconds sqlite
            'stop_time' => $this->stopTime ? $this->stopTime->toDateTimeString() : null, // TODO: milliseconds sqlite
            'performed_at' => $this->startTime->toDateTimeString(),
            'duration' => $this->getDuration(),
            'plugin_data' => $this->pluginData ? json_encode($this->pluginData->toArray()) : null,
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ];
    }

    public static function fromRecord(\stdClass $record): self {
        $loggedRequest = new self($record->raw_request, Request::fromString($record->raw_request));
        $loggedRequest->id = $record->request_id;
        $loggedRequest->startTime = Carbon::parse($record->start_time);
        $loggedRequest->stopTime = $record->stop_time ? Carbon::parse($record->stop_time) : null;
        $loggedRequest->subdomain = $record->subdomain;
        $loggedRequest->pluginData = $record->plugin_data ? PluginData::fromJson($record->plugin_data) : null;

        return $loggedRequest;
    }

    public function toArray(): array {
        return $this->jsonSerialize();
    }

    protected function isBinary(string $string): bool
    {
        return preg_match('~[^\x20-\x7E\t\r\n]~', $string) > 0;
    }

    public function getRequest()
    {
        return $this->parsedRequest;
    }

    public function setResponse(string $rawResponse, Response $response)
    {
        $this->response = new LoggedResponse($rawResponse, $response, $this->getRequest());
    }

    public function setStopTime() {
        if (is_null($this->stopTime)) {
            $this->stopTime = now();
        }
    }

    public function id(): string
    {
        return $this->id;
    }

    public function getRequestData(): ?string
    {
        return $this->rawRequest;
    }

    public function getResponse(): ?LoggedResponse
    {
        return $this->response;
    }

    public function getPostData()
    {
        $postData = [];

        $contentType = Arr::get($this->parsedRequest->getHeaders()->toArray(), 'Content-Type');
        if ($contentType && Str::contains($contentType, ";")) {
            $contentType = explode(';', $contentType, 2);
            if (is_array($contentType) && count($contentType) > 1) {
                $contentType = $contentType[0];
            }
        }

        switch ($contentType) {
            case 'application/x-www-form-urlencoded':
                parse_str($this->parsedRequest->getContent(), $postData);
                $postData = collect($postData)->map(function ($value, $key) {
                    return [
                        'name' => $key,
                        'value' => $value,
                    ];
                })->toArray();
                break;
            case 'application/json':
                $postData = collect(json_decode($this->parsedRequest->getContent(), true))->map(function ($value, $key) {
                    return [
                        'name' => $key,
                        'value' => $value,
                    ];
                })->values()->toArray();

                break;
            default:
                $stream = fopen('php://temp', 'rw');
                fwrite($stream, $this->rawRequest);
                rewind($stream);

                try {
                    $document = new StreamedPart($stream);
                    if ($document->isMultiPart()) {
                        $postData = collect($document->getParts())->map(function (StreamedPart $part) {
                            return [
                                'name' => $part->getName(),
                                'value' => $part->isFile() ? null : $part->getBody(),
                                'is_file' => $part->isFile(),
                                'filename' => $part->isFile() ? $part->getFileName() : null,
                                'mime_type' => $part->isFile() ? $part->getMimeType() : null,
                            ];
                        })->toArray();
                    }
                } catch (\Exception $e) {
                    //
                }
                break;
        }

        return $postData;
    }

    public function detectSubdomain()
    {
        return collect($this->parsedRequest->getHeaders()->toArray())
            ->mapWithKeys(function ($value, $key) {
                return [strtolower($key) => $value];
            })->get('x-original-host');
    }

    protected function getRequestId()
    {
        return collect($this->parsedRequest->getHeaders()->toArray())
            ->mapWithKeys(function ($value, $key) {
                return [strtolower($key) => $value];
            })->get('x-cloakr-request-id', (string) Str::uuid());
    }

    public function getStartTime()
    {
        return $this->startTime;
    }

    public function getDuration()
    {
        return $this->startTime->diffInMilliseconds($this->stopTime, false);  // TODO: milliseconds sqlite
    }

    protected function getRequestAsCurl(): string
    {
        $maxRequestLength = 256000;

        if (strlen($this->rawRequest) > $maxRequestLength) {
            return '';
        }

        try {
            return (new CurlFormatter())->format(Message::parseRequest($this->rawRequest));
        } catch (\Throwable $e) {
            return '';
        }
    }

    public function getUrl()
    {
        $request = Message::parseRequest($this->rawRequest);
        dd($request->getUri()->withFragment('')); // TODO: ??
    }

    public function refreshId()
    {
        $requestId = (string) Str::uuid();

        $this->getRequest()->getHeaders()->removeHeader(
            $this->getRequest()->getHeader('x-cloakr-request-id')
        );

        $this->getRequest()->getHeaders()->addHeader(new GenericHeader('x-cloakr-request-id', $requestId));

        $this->id = $requestId;
    }

    public function getCliLabel(): string {
        return $this->pluginData ? $this->pluginData->getCliLabel() : '';
    }
}
