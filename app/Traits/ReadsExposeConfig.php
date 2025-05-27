<?php
namespace Cloakr\Client\Traits;

trait ReadsCloakrConfig
{
    public function getDatabasePath(): string {
        return config('database.connections.sqlite.database');
    }

    public function getVersion(): string {
        return config('app.version');
    }
}
