<?php

namespace App\Commands;

use Illuminate\Console\Command;

use function Termwind\render;

class GetAuthenticationTokenCommand extends Command
{
    protected $signature = 'token:get {--clean}';
    protected $description = 'Retrieve the authentication token to use with Cloakr.';

    public function handle()
    {
        $token = config('cloakr.auth_token');

        if (is_null($token)) {
            render('<div class="ml-3 px-2 text-orange-600 bg-orange-100">There is no authentication token specified.</div>');
        } else {
            if ($this->option('clean') === true) {
                $this->line($token);
            } else {
                render('<div class="ml-2 my-1"><div class="text-pink-500 font-bold"><span class="font-bold pr-0.5">></span> Cloakr</div>');
                render("<div class='ml-3'>Current authentication token: <span class='font-bold'>$token</span></div>");
            }
        }
    }
}
