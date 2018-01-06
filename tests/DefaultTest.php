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

        $this->assertTrue($client->getResponse()->isOk());
    }
}
