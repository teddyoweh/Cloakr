<?php

namespace Cloakr\Client\Logger;

use Cloakr\Client\Contracts\LoggerContract;
use Cloakr\Client\Contracts\LogStorageContract;
use Cloakr\Client\Http\Resources\LogListResource;
use Cloakr\Client\RequestLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Laminas\Http\Response;

class DatabaseLogger implements LoggerContract, LogStorageContract
{
    protected Collection $requests;
    protected Collection $responses;

    protected bool $includeResponses = false;

    public function __construct()
    {
        DB::table('response_logs')->truncate();
        DB::table('request_logs')->truncate();

        $this->requests = collect();
        $this->responses = collect();
    }

    public function synchronizeRequest(LoggedRequest $loggedRequest): void
    {
        $requestExists = DB::table('request_logs')->where('request_id', $loggedRequest->id())->exists();

        if ($requestExists) {
            DB::table('request_logs')->where('request_id', $loggedRequest->id())
                ->update($loggedRequest->toDatabase());
        } else {

            $maxLogs = config('cloakr.max_logged_requests', 100);

            $requestLogs = DB::table('request_logs')->orderBy('start_time', 'desc')->get();

            if ($requestLogs->count() >= $maxLogs) {
                $oldestRequest = $requestLogs->last();
                $this->delete($oldestRequest->request_id);
            }

            DB::table('request_logs')->insert($loggedRequest->toDatabase());
        }
    }


    public function synchronizeResponse(LoggedRequest $loggedRequest, LoggedResponse $loggedResponse): void
    {
        $this->synchronizeRequest($loggedRequest);

        $responseExists = DB::table('response_logs')->where('request_id', $loggedRequest->id())->exists();

        if ($responseExists) {
            DB::table('response_logs')->where('request_id', $loggedRequest->id())->update([
                'status_code' => $loggedResponse->getStatusCode(),
                'raw_response' => $loggedResponse->getRawResponse()
            ]);

            return;
        }

        DB::table('response_logs')->insert([
            'request_id' => $loggedRequest->id(),
            'status_code' => $loggedResponse->getStatusCode(),
            'raw_response' => $loggedResponse->getRawResponse()
        ]);
    }


    public function requests(): LogStorageContract
    {
        $this->requests = DB::table('request_logs')->orderBy('start_time', 'desc')->get();
        return $this;
    }

    public function withResponses(): LogStorageContract
    {
        $this->responses = DB::table('response_logs')->get();
        $this->includeResponses = true;
        return $this;
    }

    public function withoutResponses(): LogStorageContract
    {
        $this->includeResponses = false;
        return $this;
    }


    public function get(): Collection
    {
        $hasResponses = $this->includeResponses && $this->responses->isNotEmpty();

        return $this->requests->map(function (\stdClass $logData) use ($hasResponses) {
            $loggedRequest = LoggedRequest::fromRecord($logData);

            if ($hasResponses) {
                $response = $this->responses->first(function (\stdClass $response) use ($loggedRequest) {
                    return $response->request_id === $loggedRequest->id();
                });

                if ($response) {
                    $loggedRequest->setResponse($response->raw_response, Response::fromString($response->raw_response));
                }
            }

            return $loggedRequest;
        });
    }

    public function delete(?string $id = null): void
    {
        if ($id) {
            DB::table('request_logs')->where('request_id', $id)->delete();
            return;
        }

        DB::table('request_logs')->truncate();
    }

    public function find(string $id): ?LoggedRequest
    {
        $requestLog = RequestLog::where('request_id', $id);

        if (!$requestLog) {
            return null;
        }

        $response = null;
        if ($this->includeResponses) {
            $requestLog->with('response');
        }

        $requestLog = $requestLog->first();

        $loggedRequest = LoggedRequest::fromRecord($requestLog);
        if ($response) {
            $loggedRequest->setResponse($response->raw_response, Response::fromString($response->raw_response));
        }

        return $loggedRequest;
    }

    public function getRequestList(): Collection
    {
        $requestLogs = RequestLog::query()
            ->select(['request_id', 'duration', 'request_method', 'request_uri', 'plugin_data'])
            ->with(['response:request_id,status_code'])
            ->get();

        return $requestLogs->map(function (RequestLog $requestLog) {
            return LogListResource::fromRequestLog($requestLog)->toArray();
        });
    }


}
