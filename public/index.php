<?php

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$config = new Dotenv\Dotenv(dirname(__DIR__));
$config->load();
$config->required(['APP_DEBUG', 'DB_DATABASE', 'DB_USERNAME'])->notEmpty();
$config->required('DB_PASSWORD');

$app = new Silex\Application();

$app['debug'] = getenv('APP_DEBUG');

$app->register(new Silex\Provider\DoctrineServiceProvider(), [
    'db.options' => [
        'driver' => 'pdo_mysql',
        'dbname' => getenv('DB_DATABASE'),
        'host' => '127.0.0.1',
        'user' => getenv('DB_USERNAME'),
        'password' => getenv('DB_PASSWORD'),
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
