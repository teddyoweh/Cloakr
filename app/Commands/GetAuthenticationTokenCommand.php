<?php

namespace Cloakr\Client\Commands;

use Cloakr\Client\Commands\Concerns\RendersBanner;
use Illuminate\Console\Command;

use function Termwind\render;

class GetAuthenticationTokenCommand extends Command
{
    use RendersBanner;

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
            render('<div class="ml-3 px-2 text-orange-600 bg-orange-100">There is no authentication token specified.</div>');
        } else {
            render("<div class='ml-3'>Current authentication token: <span class='font-bold'>$token</span></div>");
        }
    }
}
