<?php
namespace tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase
{
    private $client = null;

    public function setUp()
    {
        $this->client = static::createClient();
        $this->client->followRedirects(false);
    }

    public function testHomePageIsAvailable() {
        $this->client->request('GET', '/');
        $this->assertEquals(302, $this->client->getResponse()->getStatusCode());
    }

    public function testHomePageIsAvailableWithGoodCredentials() {
        $this->login();
        $this->client->request('GET', '/');
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    }

    public function testHomePageContainsSomeContent() {
        $this->login();
        $this->client->request('GET', '/');
        $this->assertContains('Very Secured data', $this->client->getResponse()->getContent());
    }

    public function testLoginForm() {
        $this->client->request('GET', '/login');
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    }

    public function testLoginFormAlreadyAuthorized() {
        $this->login();
        $this->client->request('GET', '/login');
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertEquals(null, $this->client->getResponse()->headers->get('location')); //redirect to homepage
    }

    public function testLoginFormProcess() {
        $this->logIn();

        $this->client->request('GET', '/');
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode()); // we are still authenticated

        $this->logOut();

        $this->client->request('GET', '/');
        $this->assertEquals(302, $this->client->getResponse()->getStatusCode()); // we are no more authenticated
    }

    public function testLoginFormSubmitWithWrongPassword() {
        $this->client->request('POST', '/login', [
            'username' => 'john_admin',
            'password' => '1',
            '_csrf_token' => $this->client->getContainer()->get('security.csrf.token_manager')->getToken('authenticate')->getValue(),
        ]);

        $this->assertEquals(302, $this->client->getResponse()->getStatusCode());
        $this->assertEquals('/login', $this->client->getResponse()->headers->get('location')); //fail we still on login page
    }

    private function logIn()
    {
        $this->client->request('POST', '/login', [
            'username' => 'john_admin',
            'password' => 'test',
            '_csrf_token' => $this->client->getContainer()->get('security.csrf.token_manager')->getToken('authenticate')->getValue(),
        ]);
        $this->assertEquals(302, $this->client->getResponse()->getStatusCode());
        $this->assertEquals('/', $this->client->getResponse()->headers->get('location')); //success we are inside of secured zone
    }

    private function logOut()
    {
        $this->client->request('GET', '/logout');
        $this->assertEquals(302, $this->client->getResponse()->getStatusCode()); // logout
        $this->assertEquals('http://localhost/', $this->client->getResponse()->headers->get('location')); //redirect to homepage
    }

}
