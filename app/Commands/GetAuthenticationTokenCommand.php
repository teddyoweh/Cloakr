<?php

namespace Cloakr\Client\Commands;

use Cloakr\Client\Commands\Concerns\RendersBanner;
use Cloakr\Client\Commands\Concerns\RendersOutput;
use Illuminate\Console\Command;

use function Termwind\render;

class GetAuthenticationTokenCommand extends Command
{
    use RendersBanner, RendersOutput;

    protected $signature = 'token:get';
    protected $description = 'Retrieve the authentication token to use with Cloakr.';

    public function handle()
    {
        $token = config('cloakr.auth_token');

        if ($this->option('no-interaction') === true) {
            $this->line($token ?? '');
            return;
        }

        $this->renderBanner();

        if (is_null($token)) {
            $this->renderWarning('There is no authentication token specified.');
        } else {
            render("<div class='ml-3'>Current authentication token: <span class='font-bold'>$token</span></div>");
        }
    }
}
