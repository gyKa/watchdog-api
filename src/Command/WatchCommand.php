<?php

namespace Command;

use GuzzleHttp\Client;
use GuzzleHttp\TransferStats;
use ValueObject\Response;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WatchCommand extends Command
{
    private $client;

    private $input;

    private $output;

    private $responses = [];

    public function __construct(Client $client)
    {
        $this->client = $client;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('watch')
            ->addArgument('url', InputArgument::REQUIRED)
        ;
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        $this->request();
        $this->register();
    }

    private function request()
    {
        $onStat = function (TransferStats $stats) {
            if ($stats->hasResponse()) {
                $this->log($stats);

                return;
            }

            var_dump($stats->getHandlerErrorData());
        };

        $this->client->request(
            'GET',
            $this->input->getArgument('url'),
            [
                'on_stats' => $onStat,
            ]
        );
    }

    private function log(TransferStats $stats)
    {
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
            $this->client->request(
                'POST',
                getenv('API_ENDPOINT'),
                [
                    'json' => [
                        'url' => $response->getUrl(),
                        'http_code' => $response->getHttpCode(),
                        'total_time' => $response->getTotalTime(),
                    ],
                ]
            );
        }
    }
}

