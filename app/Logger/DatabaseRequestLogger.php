<?php

namespace Cloakr\Client\Logger;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Laminas\Http\Response;

class DatabaseRequestLogger extends Logger
{

    public function __construct()
    {
        DB::table('response_logs')->truncate();
        DB::table('request_logs')->truncate();
    }

    public function logRequest(LoggedRequest $loggedRequest)
    {
        // TODO:
        // - max logs
        $requestExists = DB::table('request_logs')->where('request_id', $loggedRequest->id())->exists();

        if ($requestExists) {
            DB::table('request_logs')->where('request_id', $loggedRequest->id())
                ->update($loggedRequest->toDatabase());
        } else {

            $maxLogs = config('cloakr.max_logged_requests', 10);

            $requestLogs = DB::table('request_logs')->orderBy('start_time', 'desc')->get();

            if ($requestLogs->count() >= $maxLogs) {
                $oldestRequest = $requestLogs->last();
                DB::table('request_logs')->where('request_id', $oldestRequest->request_id)->delete();

                // TODO: remove oldest request from frontend
            }

            DB::table('request_logs')->insert($loggedRequest->toDatabase());
        }
    }

    public function saveResponse(LoggedRequest $loggedRequest, string $rawResponse) // TODO: better method name / structure
    {
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

    public function getData(): array
    {
        $logs = DB::table('request_logs')->orderBy('start_time', 'desc')->get();
        $responses = DB::table('response_logs')->get();

        $logs = $logs->map(function (\stdClass $logData) use ($responses) {
            $loggedRequest = LoggedRequest::fromRecord($logData);

            $response = $responses->first(function (\stdClass $response) use ($loggedRequest) {
                return $response->request_id === $loggedRequest->id();
            });

            if ($response) {
                $loggedRequest->setResponse($response->raw_response, Response::fromString($response->raw_response));
            }

            return $loggedRequest;
        });

        return $logs->toArray();
    }
}
