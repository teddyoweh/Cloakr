<?php

namespace App\Commands\Concerns;

use function Termwind\render;

trait RendersBanner
{
    public function renderBanner(): void
    {
        render('<div class="ml-2 text-pink-500 font-bold"><span class="pr-0.5">></span> Cloakr</div>');
    }
}
