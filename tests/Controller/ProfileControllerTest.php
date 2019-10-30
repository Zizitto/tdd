<?php
namespace tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProfileControllerTest extends WebTestCase
{
    private $client = null;

    public function setUp()
    {
        $this->client = static::createClient();
        $this->client->followRedirects(false);
    }

    public function testProfilePageIsFormAvailable() {
        $this->login();
        $crawler = $this->client->request('GET', '/profile');

        $this->assertContains('Profile page', $this->client->getResponse()->getContent());

        $form = $crawler->selectButton('Save')->form();
        $this->assertNotEmpty($form);
    }

    public function testProfilePageSubmitValidForm() {
        $this->login();
        $crawler = $this->client->request('GET', '/profile');

        $form = $crawler->selectButton('Save')->form();

        $form['form[username]'] = 'test1';
        $this->assertNotEmpty($form);

        $this->client->submit($form);

        $this->assertEquals(302, $this->client->getResponse()->getStatusCode());
        $this->assertContains('?username=test1', $this->client->getResponse()->headers->get('Location'));
    }

    public function testProfilePageSubmitNotValidForm() {
        $this->login();
        $crawler = $this->client->request('GET', '/profile');

        $form = $crawler->selectButton('Save')->form();

        $form['form[username]'] = 't';
        $this->assertNotEmpty($form);

        $this->client->submit($form);

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertContains('Profile page', $this->client->getResponse()->getContent());
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
