<?php

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

$config = new Dotenv\Dotenv(dirname(__DIR__));
$config->load();
$config->required(['APP_DEBUG', 'DB_DATABASE', 'DB_USERNAME'])->notEmpty();
$config->required('DB_PASSWORD');

$app = new Silex\Application();

$app['debug'] = filter_var(getenv('APP_DEBUG'), FILTER_VALIDATE_BOOLEAN);

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
$app->register(new Silex\Provider\ValidatorServiceProvider());
$app->register(new Silex\Provider\TwigServiceProvider(), [
    'twig.path' => __DIR__ . '/../views',
]);

$app->get('/', function () use ($app) {
    $sql = '
        SELECT
            (SELECT COUNT(*) FROM `logs`) as total_entries,
            (SELECT COUNT(*) FROM `logs` WHERE http_code NOT IN (200, 301, 302)) as total_fails,
            (SELECT COUNT(*) FROM `logs` WHERE http_code IN (301, 302)) as total_redirects,
            (SELECT created_at
             FROM `logs`
             WHERE 1
                 AND http_code NOT IN (200, 301, 302)
             ORDER BY created_at DESC
             LIMIT 1) as latest_created_fail,
            (SELECT created_at FROM `logs` ORDER BY created_at DESC LIMIT 1) as latest_created_entry
    ';

    $result = $app['db']->fetchAssoc($sql);

    return $app['twig']->render('index.twig', [
        'total_entries' => $result['total_entries'],
        'total_fails' => $result['total_fails'],
        'total_redirects' => $result['total_redirects'],
        'latest_created_entry' => $result['latest_created_entry'],
    ]);
});

$app->post('/collect', function (Request $request) use ($app) {
    if (strpos($request->headers->get('Content-Type'), 'application/json') !== 0) {
        return new Response(null, Response::HTTP_BAD_REQUEST);
    }

    $response = [
        'success' => true,
        'errors' => [],
    ];

    $data = json_decode($request->getContent(), true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        $response['success'] = false;
        $response['errors'][] = json_last_error_msg();

        return new JsonResponse($response, Response::HTTP_BAD_REQUEST);
    }

    $constraint = new Assert\Collection(
        [
            'url' => [new Assert\Length(['min' => 12])],
            'http_code' => [
                new Assert\Type(['type' => 'integer']),
                new Assert\Length(['min' => 3]),
                new Assert\Length(['max' => 3]),
            ],
            'total_time' => [new Assert\Type(['type' => 'float'])],
        ]
    );

    /** @var ValidatorInterface $validator */
    $validator = $app['validator'];
    $errors = $validator->validate($data, $constraint);

    if (count($errors) > 0) {
        $errorMessages = [];

        foreach ($errors as $error) {
            $errorMessages[] = sprintf('%s %s', $error->getPropertyPath(), $error->getMessage());
        }

        $response['success'] = false;
        $response['errors'] = $errorMessages;

        return new JsonResponse($response, Response::HTTP_BAD_REQUEST);
    }

    $app['db']->insert(
        'logs',
        [
            'url' => $data['url'],
            'http_code' => $data['http_code'],
            'total_time' => $data['total_time']
        ]
    );

    return new JsonResponse($response, Response::HTTP_CREATED);
});

$app->run();
