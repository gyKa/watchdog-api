<?php

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$app = new Silex\Application();

$app->get('/', function () {
    return time();
});

$app->post('/collect', function (Request $request) {
    return new Response(null, 201);
});

$app->run();
