<?php

namespace Tests\Feature;

use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

use function React\Async\await;

abstract class TestCase extends \Tests\TestCase
{
    const AWAIT_TIMEOUT = 5.0;

    /** @var LoopInterface */
    protected $loop;

    public function setUp(): void
    {
        parent::setUp();

        $this->app->bind(ConsoleOutputInterface::class, function () {
            return new ConsoleOutput();
        });

        /** @var LoopInterface $loop */
        $this->loop = $this->app->make(LoopInterface::class);
    }

    protected function await(PromiseInterface $promise)
    {
        return await($promise);
    }
}
