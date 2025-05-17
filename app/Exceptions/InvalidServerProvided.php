<?php

namespace Cloakr\Client\Exceptions;

class InvalidServerProvided extends \Exception
{
    public function __construct($server)
    {
        $message = "No such server {$server}.";

        parent::__construct($message);
    }
}
