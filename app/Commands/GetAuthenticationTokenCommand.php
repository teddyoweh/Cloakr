<?php

namespace Cloakr\Client\Commands;

use Illuminate\Console\Command;

use function Cloakr\Common\banner;
use function Cloakr\Common\warning;
use function Cloakr\Common\info;

class GetAuthenticationTokenCommand extends Command
{


    protected $signature = 'token:get';
    protected $description = 'Retrieve the authentication token to use with Cloakr.';

    public function handle()
    {
        $token = config('cloakr.auth_token');

        if ($this->option('no-interaction') === true) {
            $this->line($token ?? '');
            return;
        }

        banner();

        if (empty($token)) {
            warning('There is no authentication token specified.');
        } else {
            info("Current authentication token: <span class='font-bold'>$token</span>");
        }
    }
}
