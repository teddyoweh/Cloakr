<?php

namespace Cloakr\Client\Commands;

use Cloakr\Client\Contracts\FetchesPlatformDataContract;
use Cloakr\Client\Traits\FetchesPlatformData;
use Illuminate\Support\Facades\Artisan;

class SetUpCloakrProToken implements FetchesPlatformDataContract
{
    use FetchesPlatformData;

    protected string $token;


    public function __invoke(string $token)
    {
        if (!$this->cloakrPlatformSetup()) return;

        $this->token = $token;

        if ($this->isProToken() && $this->hasTeamDomains()) {
             (new SetUpCloakrDefaultDomain)($token);
        } else {
             (new SetUpCloakrDefaultServer)($token);
        }
    }

    public function getToken(): string
    {
        return $this->token;
    }
}
