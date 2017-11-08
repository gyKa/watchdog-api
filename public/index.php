<?php

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$app = new Silex\Application();

$app->register(new Silex\Provider\DoctrineServiceProvider(), [
    'db.options' => [
        'driver' => 'pdo_mysql',
        'dbname' => 'watchdog',
        'host' => '127.0.0.1',
        'user' => 'root',
        'password' => 'secret',
        'charset' => 'utf8mb4',
    ],
]);

$app->get('/', function () {
    return time();
});

$app->post('/collect', function (Request $request) use ($app) {
    if (strpos($request->headers->get('Content-Type'), 'application/json') !== 0) {
        return new Response(null, Response::HTTP_BAD_REQUEST);
    }

    $data = json_decode($request->getContent(), true);

    $app['db']->insert(
        'logs',
        [
            'url' => $data['url'],
            'http_code' => $data['http_code'],
            'total_time' => $data['total_time']
        ]
    );

    return new Response(null, Response::HTTP_CREATED);
});

$app->run();
