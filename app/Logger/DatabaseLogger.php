<?php

namespace Cloakr\Client\Logger;

use Cloakr\Client\Contracts\LoggerContract;
use Cloakr\Client\Contracts\LogStorageContract;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Laminas\Http\Response;

class DatabaseLogger extends Logger implements LoggerContract, LogStorageContract
{

    protected Collection $requests;
    protected Collection $responses;

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

            $maxLogs = config('cloakr.max_logged_requests', 10);

            $requestLogs = DB::table('request_logs')->orderBy('start_time', 'desc')->get();

            if ($requestLogs->count() >= $maxLogs) {
                $oldestRequest = $requestLogs->last();
                $this->delete($oldestRequest->request_id);
            }

            DB::table('request_logs')->insert($loggedRequest->toDatabase());
        }
    }


    public function synchronizeResponse(LoggedRequest $loggedRequest, string $rawResponse): void
    {
        $this->synchronizeRequest($loggedRequest);

        $responseExists = DB::table('response_logs')->where('request_id', $loggedRequest->id())->exists();

        if ($responseExists) {
            DB::table('response_logs')->where('request_id', $loggedRequest->id())->update([
                'raw_response' => $rawResponse
            ]);

            return;
        }

        DB::table('response_logs')->insert([
            'request_id' => $loggedRequest->id(),
            'raw_response' => $rawResponse
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
        return $this;
    }


    public function get(): Collection
    {
        $hasResponses = $this->responses->isNotEmpty();

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
        if($this->requests->isEmpty()) {
            $this->requests();
        }

        $log = $this->requests->first(function (\stdClass $log) use ($id) {
            return $log->request_id === $id;
        });

        if (!$log) {
            return null;
        }

        return LoggedRequest::fromRecord($log);
    }


}
