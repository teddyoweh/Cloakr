<?php

namespace App\Commands\Concerns;

use function Termwind\render;

trait RendersLineTable
{

    function renderLineTable(array $data)
    {

        $template = <<<HTML
        <div class="flex ml-3 mr-6">
            <span>key</span>
            <span class="flex-1 content-repeat-[.] text-gray-800"></span>
            <span>value</span>
        </div>
    HTML;

        foreach ($data as $key => $value) {
            $output = str_replace(
                ['key', 'value'],
                [$key, $value],
                $template
            );

            render($output);
        }
    }

    function lineTableLabel(?string $key)
    {
        return match ($key) {
            'token' => 'Token',
            'default_server' => 'Default Server',
            'default_domain' => 'Default Domain',
            'plan' => 'Plan',
            'version' => 'Version',
            'latency' => 'Latency',
            'free' => 'Cloakr Free',
            'pro' => 'Cloakr Pro',
            null => 'None',
            default => $key
        };
    }
}
