<?php

namespace Cloakr\Client\Http;

use Cloakr\Client\Http\Controllers\FileController;
use Cloakr\Common\Http\RouteGenerator;
use Symfony\Component\Routing\Route;

class ClientRouteGenerator extends RouteGenerator
{
    public function addPublicFilesystem()
    {
        $this->routes->add('get-files', new Route(
            '/files/{path}',
            ['_controller' => FileController::class],
            ['path' => '.*']
        ));
    }
}
