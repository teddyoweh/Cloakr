<?php

namespace Cloakr\Client\Http\Controllers;

use Cloakr\Client\Contracts\LogStorageContract;
use Cloakr\Common\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Ratchet\ConnectionInterface;

class GetLogsController extends Controller
{
    public function __construct(protected LogStorageContract $logStorage)
    {
    }

    public function handle(Request $request, ConnectionInterface $httpConnection)
    {
        $httpConnection->send(respond_json($this->logStorage->getRequestList()->toArray()));
    }
}
