<?php

namespace Tests;

use Cloakr\Client\Contracts\LogStorageContract;
use LaravelZero\Framework\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');

        $this->artisan('migrate')->run();

        // Rebind the log storage contract to use the correct database connection for testing in the
        // DatabaseLogger class
        app(LogStorageContract::class);
    }
}
