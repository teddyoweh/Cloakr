<?php

namespace Cloakr\Client\Logger;

use Cloakr\Client\Contracts\LoggerContract;
use Cloakr\Client\Contracts\LogStorageContract;
use Cloakr\Client\Http\Resources\LogListResource;
use Cloakr\Client\RequestLog;
use Cloakr\Client\ResponseLog;
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
        $requestExists = RequestLog::where('request_id', $loggedRequest->id())->exists();

        if ($requestExists) {
            RequestLog::where('request_id', $loggedRequest->id())
                ->update($loggedRequest->toDatabase());
        } else {

            $maxLogs = config('cloakr.max_logged_requests', 100);

            $requestLogsCount = RequestLog::count();

            if ($requestLogsCount >= $maxLogs) {
                $oldestRequest = RequestLog::orderBy('start_time', 'asc')->first();
                $this->delete($oldestRequest->request_id);
            }

            DB::table('request_logs')->insert($loggedRequest->toDatabase());
        }
    }


    public function synchronizeResponse(LoggedRequest $loggedRequest, LoggedResponse $loggedResponse): void
    {
        $this->synchronizeRequest($loggedRequest);

        $responseExists = ResponseLog::where('request_id', $loggedRequest->id())->exists();

        if ($responseExists) {
            ResponseLog::where('request_id', $loggedRequest->id())->update([
                'status_code' => $loggedResponse->getStatusCode(),
                'raw_response' => $loggedResponse->getRawResponse()
            ]);

            return;
        }

        ResponseLog::insert([
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
        if ($this->includeResponses) {
            $this->requests();
        }

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

        if ($this->includeResponses) {
            $requestLog->with('response');
        }

        $requestLog = $requestLog->first();

        if (!$requestLog) {
            return null;
        }


        $loggedRequest = LoggedRequest::fromRecord($requestLog);

        if ($requestLog->response) {
            $loggedRequest->setResponse($requestLog->response->raw_response, Response::fromString($requestLog->response->raw_response));
        }

        return $loggedRequest;
    }

    public function search(string $search): Collection
    {
        return RequestLog::query()
            ->select([
                "request_id",
                "duration",
                "raw_request",
                "request_method",
                "request_uri",
                "plugin_data",
                "status_code"
            ])
            ->with(["response:request_id,raw_response,status_code"])
            ->where("request_uri", "like", "%$search%")
            ->orWhere("plugin_data", "like", "%$search%")
            ->orWhereHas("response", function ($query) use ($search) {
                // Search response only if request contains a searchable content type
                // ["application/json", "application/ld-json", "text/html", "text/plain"]
                $query
                    ->whereHas("request", function ($query) {
                        $query->where(function ($query) {
                            $query
                                ->where("raw_request", "like", "%application/json%")
                                ->orWhere("raw_request", "like", "%application/ld-json%")
                                ->orWhere("raw_request", "like", "%text/html%")
                                ->orWhere("raw_request", "like", "%text/plain%");
                        });
                    })
                    ->whereRaw("CAST(raw_response AS TEXT) LIKE ?", ["%$search%"]);
            })
            ->orderBy("start_time", "desc")
            ->get()
            ->map(function (RequestLog $requestLog) {
                return LogListResource::fromRequestLog($requestLog)->toArray();
            });
    }

    public function getRequestList(): Collection
    {
        return RequestLog::query()
            ->select(['request_id', 'duration', 'request_method', 'request_uri', 'plugin_data'])
            ->with(['response:request_id,status_code'])
            ->orderBy('start_time', 'desc')
            ->get()
            ->map(function (RequestLog $requestLog) {
                return LogListResource::fromRequestLog($requestLog)->toArray();
            });
    }


}
