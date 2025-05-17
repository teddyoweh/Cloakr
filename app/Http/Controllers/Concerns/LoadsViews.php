<?php

namespace App\Http\Controllers\Concerns;

use GuzzleHttp\Psr7\Utils;
use Ratchet\ConnectionInterface;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

trait LoadsViews
{
    protected function getView(?ConnectionInterface $connection, string $view, array $data = [])
    {
        $templatePath = implode(DIRECTORY_SEPARATOR, explode('.', $view));

        $twig = new Environment(
            new ArrayLoader([
                'app' => file_get_contents(base_path('resources/views/server/layouts/app.twig')),
                'template' => file_get_contents(base_path('resources/views/'.$templatePath.'.twig')),
            ])
        );

        $data = array_merge($data, [
            'request' => $connection->laravelRequest ?? null,
        ]);
        try {
            return Utils::streamFor($twig->render('template', $data));
        } catch (\Throwable $e) {
            var_dump($e->getMessage());
        }
    }

    protected function getBlade(?ConnectionInterface $connection, string $view, array $data = [])
    {
        return Utils::streamFor(view($view, $data));
    }
}
