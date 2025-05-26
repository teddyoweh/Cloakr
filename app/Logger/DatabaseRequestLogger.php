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
        // - fetch: order by updated_at, id
        $requestExists = DB::table('request_logs')->where('request_id', $loggedRequest->id())->exists();

        if ($requestExists) {
            DB::table('request_logs')->where('request_id', $loggedRequest->id())
                ->update(array_merge(
                    $loggedRequest->toDatabase(),
                    [
                        'updated_at' => now(),
                    ]
                ));
        } else {
            DB::table('request_logs')->insert($loggedRequest->toDatabase());
        }
    }

    public function saveResponse(LoggedRequest $loggedRequest, string $rawResponse) // TODO: better method name / structure
    {
        $responseExists = DB::table('response_logs')->where('request_id', $loggedRequest->id())->exists();

        if ($responseExists) {
            DB::table('response_logs')->where('request_id', $loggedRequest->id())->update([
                'raw_response' => $rawResponse,
                'updated_at' => now(),
            ]);

            return;
        }

        DB::table('response_logs')->insert([
            'request_id' => $loggedRequest->id(),
            'raw_response' => $rawResponse,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function getData(): array
    {
        $logs = DB::table('request_logs')->get();

        $logs = $logs->map(function (\stdClass $logData) {
            $loggedRequest = LoggedRequest::fromRecord($logData);

            $response = DB::table('response_logs')->where('request_id', $loggedRequest->id())->first();
            if ($response) {
                try {
                    $parsedResponse = Response::fromString($response->raw_response);
                }
                catch (\Exception $e) {
                    $parsedResponse = null;
                }
                $loggedRequest->setResponse($response->raw_response, $parsedResponse);
            }

            return $loggedRequest;
        });

        return $logs->toArray();
    }
}
