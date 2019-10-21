<?php
namespace tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase
{
    public function testHomePageIsAvailable() {
        $client = static::createClient();
        $client->request('GET', '/');
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
    }

    public function testHomePageContainsSomeContent() {
        $client = static::createClient();
        $client->request('GET', '/');
        $this->assertContains('Very Secured data', $client->getResponse()->getContent());
    }

}
