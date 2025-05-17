<?php

namespace App\Commands\Concerns;

use function Termwind\render;

trait RendersOutput
{
    public function renderWarning(string $message): void
    {
        render("<div class='ml-3 px-2 text-orange-600 bg-orange-100'>$message</div>");
    }
}
