<?php

use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Psr7\Response;

use function Termwind\render;

function respond_json($responseData, int $statusCode = 200)
{
    return Message::toString(new Response(
        $statusCode,
        ['Content-Type' => 'application/json'],
        json_encode($responseData, JSON_INVALID_UTF8_IGNORE)
    ));
}

function respond_html(string $html, int $statusCode = 200)
{
    return Message::toString(new Response(
        $statusCode,
        ['Content-Type' => 'text/html'],
        $html
    ));
}

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

function lineTableLabel(?string $key) {
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
