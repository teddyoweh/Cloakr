<?php

namespace Cloakr\Client\Commands\Support;

use Cloakr\Client\Contracts\FetchesPlatformDataContract;
use Cloakr\Client\Traits\FetchesPlatformData;
use function Cloakr\Common\error;

class ValidateCloakrToken implements FetchesPlatformDataContract
{
    use FetchesPlatformData;

    protected string $token;

    public function __invoke(string $token)
    {
        if (!$this->cloakrPlatformSetup()) return;

        $this->token = $token;

        $cloakrToken = $this->cloakrToken();

        if ($cloakrToken->isInvalid()) {
            error("Token $this->token is invalid. Please check your token and try again. If you don't have a token, visit <a href='https://cloakr.dev'>cloakr.dev</a> to create your free account.");
            exit;
        }
    }

    public function getToken()
    {
        return $this->token;
    }
}
