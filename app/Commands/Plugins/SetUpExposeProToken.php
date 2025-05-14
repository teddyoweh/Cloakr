<?php

namespace App\Commands\Plugins;

use App\Contracts\FetchesPlatformDataContract;
use App\Traits\FetchesPlatformData;

class SetupCloakrProToken implements FetchesPlatformDataContract
{
    use FetchesPlatformData;

    protected string $token;


    public function __invoke(string $token)
    {
        if (!$this->cloakrPlatformSetup()) return;

        $this->token = $token;

        if ($this->isProToken() && $this->hasTeamDomains()) {
            return (new SetUpCloakrDefaultDomain)($token);
        } else {
            return (new SetUpCloakrDefaultServer)($token);
        }
    }

    public function getToken()
    {
        return $this->token;
    }
}
