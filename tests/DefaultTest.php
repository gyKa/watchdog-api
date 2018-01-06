<?php

namespace Tests;

use Silex\WebTestCase;

class DefaultTest extends WebTestCase
{
    public function createApplication()
    {
        return require __DIR__ . '/../public/index.php';
    }

    public function testIndex()
    {
        $client = $this->createClient();
        $client->request('GET', '/');

        $response = $client->getResponse();

        if ($response === null) {
            throw new \RuntimeException('Client got no response');
        }

        $this->assertTrue($response->isOk());
    }
}
