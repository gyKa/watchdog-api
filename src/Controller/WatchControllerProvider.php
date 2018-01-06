<?php

namespace Controller;

use GuzzleHttp\Client;
use GuzzleHttp\TransferStats;
use Silex\Api\ControllerProviderInterface;
use Silex\Application;
use ValueObject\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class WatchControllerProvider implements ControllerProviderInterface
{
    /** @var Application */
    private $app;

    /** @var array */
    private $responses = [];

    public function connect(Application $app)
    {
        $this->app = $app;

        // Creates a new controller based on the default route.
        $controllers = $app['controllers_factory'];

        $controllers->get('/', function () {
            return $this->watchAction();
        });

        $controllers->get('/info/{protocol}/{domain}/{tld}', function ($protocol, $domain, $tld) {
            $fullUrl = sprintf('%s://%s.%s', $protocol, $domain, $tld);

            return $this->infoAction($fullUrl);
        });

        $controllers->get('/info/{protocol}/www/{domain}/{tld}', function ($protocol, $domain, $tld) {
            $fullUrl = sprintf('%s://www.%s.%s', $protocol, $domain, $tld);

            return $this->infoAction($fullUrl);
        });

        $controllers->get('/{protocol}/{domain}/{tld}', function ($protocol, $domain, $tld) {
            $fullUrl = sprintf('%s://%s.%s', $protocol, $domain, $tld);

            return $this->watchUrlAction($fullUrl);
        });

        $controllers->get('/{protocol}/www/{domain}/{tld}', function ($protocol, $domain, $tld) {
            $fullUrl = sprintf('%s://www.%s.%s', $protocol, $domain, $tld);

            return $this->watchUrlAction($fullUrl);
        });

        return $controllers;
    }

    private function infoAction($url)
    {
        $this->request($url);

        $results = [];

        foreach ($this->responses as $response) {
            $results[] = [
                'url' => $response->getUrl(),
                'http_code' => $response->getHttpCode(),
                'total_time' => $response->getTotalTime()
            ];
        }

        return $this->app->json($results);
    }

    private function watchAction()
    {
        $sql = '
            SELECT
                url
            FROM urls
        ';

        $urls = $this->app['db']->fetchAll($sql);

        foreach ($urls as $urlRow) {
            $this->request($urlRow['url']);
        }

        $this->register();

        return $this->app->json([], HttpResponse::HTTP_CREATED);
    }

    private function watchUrlAction(string $url)
    {
        $this->request($url);
        $this->register();

        return $this->app->json([], HttpResponse::HTTP_CREATED);
    }

    /**
     * @param string $url \
     * @throws \RuntimeException
     * @throws \DomainException
     */
    private function request(string $url)
    {
        $client = new Client();

        $onStat = function (TransferStats $stats) {
            if ($stats->hasResponse()) {
                $this->log($stats);

                return;
            }

            throw new \RuntimeException('No response: ' . print_r($stats->getHandlerErrorData(), true));
        };

        $client->request('GET', $url, ['on_stats' => $onStat]);
    }

    /**
     * @param TransferStats $stats
     * @throws \DomainException
     */
    private function log(TransferStats $stats)
    {
        if ($stats->getResponse() === null) {
            throw new \DomainException('TransferStats object has no Response');
        }

        $this->responses[] = new Response(
            $stats->getEffectiveUri(),
            $stats->getResponse()->getStatusCode(),
            $stats->getTransferTime()
        );
    }

    private function register()
    {
        /** @var Response $response */
        foreach ($this->responses as $response) {
            $this->app['db']->insert(
                'logs',
                [
                    'url' => $response->getUrl(),
                    'http_code' => $response->getHttpCode(),
                    'total_time' => $response->getTotalTime()
                ]
            );
        }
    }
}
