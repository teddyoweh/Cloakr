<?php

namespace Cloakr\Client\Commands;

use Cloakr\Client\Contracts\FetchesPlatformDataContract;
use Cloakr\Client\Traits\FetchesPlatformData;
use LaravelZero\Framework\Commands\Command;
use function Cloakr\Common\banner;
use function Cloakr\Common\error;
use function Cloakr\Common\info;

class ValidateTokenCommand extends Command implements FetchesPlatformDataContract
{
    use FetchesPlatformData;

    protected $signature = 'token:validate {token?}';

    protected $description = 'Validate the current Cloakr token.';

    protected string $token;

    public function handle()
    {
        if (!$this->cloakrPlatformSetup()) return;

        if (!$this->option('no-interaction')) {
            banner();
        }

        $this->token = $this->argument('token') ?? config('cloakr.auth_token');

        $cloakrToken = $this->cloakrToken();

        if ($cloakrToken->isInvalid()) {
            error("Token $this->token is invalid. Please check your token and try again. If you don't have a token, visit cloakr.dev to create your free account.");
            exit;
        } else {
            if (!$this->option('no-interaction')) {
                info("Token $this->token is valid." . ($cloakrToken->isPro() ? " Thanks for using Cloakr Pro! 💎" : ""));
            }
        }
    }

    public function getToken(): string
    {
        return $this->token;
    }


}
