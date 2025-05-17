<?php

namespace Cloakr\Client\Http\Controllers;

use Cloakr\Client\Client;
use Cloakr\Common\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Ratchet\ConnectionInterface;

class GetTunnelsController extends Controller
{
    public function handle(Request $request, ConnectionInterface $httpConnection)
    {
        $httpConnection->send(respond_json([
            'tunnels' => Client::$subdomains,
        ], 200));
    }
}
