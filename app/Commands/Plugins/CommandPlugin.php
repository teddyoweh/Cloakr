<?php

namespace App\Commands\Plugins;

abstract class CommandPlugin {
    abstract function __invoke(...$parameters);
}