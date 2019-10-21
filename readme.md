## TDD of login process to admin part.
### Planning
What we need to do?
We need to have secured page which will show some data and require user authentication:

Let`s split this task to few smaller tasks:
- Start new project
- Create page (homepage).
- Fill Homepage with some very secured content.
- Protect it with access policy
- Add login form

# 1. New Project And Setup

- Install Symfony installer

	- curl -sS https://get.symfony.com/cli/installer | bash
	- mv ~/.symfony/bin/symfony /usr/local/bin/symfony

- Create project

	- symfony new tdd --full

- Open Project with PHPStorm
- Setup PHPStorm PHP and PHPUnit Configuration

- (optional) Init git repository
git init
git remote add origin git@github.com:zzz/zzz.git
- (optional) Start New Branch with name "login"

# 2. Create homepage

### Write test:
Create DefaultControllerTest.php inside of "tests/Controller" dirrectory.
Extend it from WebTestCase class.

	namespace tests\Controller;

	use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

	class DefaultControllerTest extends WebTestCase
	{
	    private $client = null;

	    public function setUp()
	    {
	        $this->client = static::createClient();
	    }
    }

Add homepage test function:

	public function testHomePageIsAvailable() {
	    $this->client->request('GET', '/');
	    $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
	}

### Run test:

	Failed asserting that 404 matches expected 200.
	Expected :200
	Actual   :404

### Fix test:
Add DefaultController with "\" route

	<?php

	namespace App\Controller;

	use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
	use Symfony\Component\HttpFoundation\Response;
	use Symfony\Component\Routing\Annotation\Route;

	class DefaultController extends AbstractController
	{
	    /**
	     * Homepage
	     *
	     * @Route("/", name="home", methods={"GET"})
	     * @return Response
	     */
	    public function homeAction() {
	        return $this->render('home.html.twig');
	    }
	}

Add home.html.twig to templates

	{% extends 'base.html.twig' %}

	{% block body %}
	hello
	{% endblock %}

### Run test:

	OK (1 test, 1 assertion)

### Refactoring (not needed)

# 3. Fill Homepage with some content.

### Write test:
Add test function to DefaultControllerTest.php:

	public function testHomePageContainsSomeContent() {
	    $this->client->request('GET', '/');
	    $this->assertContains('Very Secure data', $this->client->getResponse()->getContent());
	}

### Run test:

	Failed asserting that '<!DOCTYPE html>\n
	<html>\n
	<head>\n
	<meta charset="UTF-8">\n
	<title>Welcome!</title>\n
	</head>\n
	<body>\n
	hello\n
	</body>\n
	</html>\n
	' contains "Very Secured data".

### Fix test:
Add content to home.html.twig:

	{% extends 'base.html.twig' %}

	{% block body %}
	Very Secured data
	{% endblock %}

### Run test:

	OK (1 test, 1 assertion)

### Run all tests

	OK (2 tests, 2 assertions)

### Refactoring
assertContains can be replaced by assertSame with crowler get element text


# 4. Protect it with login process

### Write test
Modify testHomePageIsAvailable to have 401 by default

	public function testHomePageIsAvailable() {
	    $this->client->request('GET', '/');
	    $this->assertEquals(401, $this->client->getResponse()->getStatusCode());
	}

### Run test:
Failed asserting that 200 matches expected 401.

### Fix test:
Add rule to access controll

	- { path: ^/, roles: ROLE_ADMIN }

### Run testHomePageIsAvailable

	OK (1 test, 1 assertion)

### Run all tests

	Tests: 2, Assertions: 2, Failures: 1.

### Fix test 
Add testHomePageIsAvailableWithGoodCredentials test

	public function testHomePageIsAvailableWithGoodCredentials() {
	    $this->login();
	    $this->client->request('GET', '/');
	    $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
	}

	private function logIn()
	{
	    $session = $this->client->getContainer()->get('session');
	    $firewallName = 'main';
	    $firewallContext = 'main';
	    $token = new UsernamePasswordToken('admin', null, $firewallName, ['ROLE_ADMIN']);
	    $session->set('_security_'.$firewallContext, serialize($token));
	    $session->save();

	    $cookie = new Cookie($session->getName(), $session->getId());
	    $this->client->getCookieJar()->set($cookie);
	}

# 5. Add login form
### Write Test

	public function testLoginForm() {
	    $this->client->request('GET', '/login');
	    $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
	}

### Run tests
Failed asserting that 404 matches expected 200.

### Fix test
Add password encoder and InMemory users

	    encoders:
	        Symfony\Component\Security\Core\User\User: 'auto'

	    # https://symfony.com/doc/current/security.html#where-do-users-come-from-user-providers
	    providers:
	        in_memory:
	            memory:
	                users:
	                    john_admin: { password: '$argon2id$v=19$m=65536,t=4,p=1$w7EtYjXVZV/wXDBuogsF1w$vR4MMumgpX4FqWTlMnJJnTfG6+unZSSVhZazMsrOqBc', roles: ['ROLE_ADMIN'] }
	                    jane_admin: { password: '$argon2id$v=19$m=65536,t=4,p=1$kMNjXkDDcYNScHG7W8MA3w$0V6OS2FwryNdLLOOBszAGC9cYt++JCHwJwc9gRfEVzk', roles: ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN'] }

Add access control rule:

	access_control:
	 - { path: ^/login, roles: IS_AUTHENTICATED_ANONYMOUSLY }
	 - { path: ^/, roles: ROLE_ADMIN }

Generate login form (use Symfony\Component\Security\Core\User\User as User Entity)

	bin/console make:auth

### Run testLoginForm

	OK (1 test, 1 assertion)

### Run all tests
	Failed testHomePageIsAvailable

### Fix testHomePageIsAvailable 
Modify testHomePageIsAvailable to have 302 redirect by default

	public function testHomePageIsAvailable() {
	    $this->client->request('GET', '/');
	    $this->assertEquals(302, $this->client->getResponse()->getStatusCode());
	}

### Refactoring (Add extra tests)

### #Write success test

	public function testLoginFormSubmit() {
	    $this->client->request('POST', '/login', [
	        'username' => 'john_admin',
	        'password' => 'test',
	        '_csrf_token' => $this->client->getContainer()->get('security.csrf.token_manager')->getToken('authenticate')->getValue(),
	    ]);

	    $this->assertEquals(302, $this->client->getResponse()->getStatusCode());
	    $this->assertEquals('/', $this->client->getResponse()->headers->get('location')); //success we are inside of secured zone
	}

### #Write fail test

	public function testLoginFormSubmitWithWrongPassword() {
	    $this->client->request('POST', '/login', [
	        'username' => 'john_admin',
	        'password' => '1',
	        '_csrf_token' => $this->client->getContainer()->get('security.csrf.token_manager')->getToken('authenticate')->getValue(),
	    ]);

	    $this->assertEquals(302, $this->client->getResponse()->getStatusCode());
	    $this->assertEquals('/login', $this->client->getResponse()->headers->get('location')); //fail we still on login page
	}

### #Write login logout flow test

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
	    $this->assertEquals('/login', $this->client->getResponse()->headers->get('location')); //fail, we still on login page
	}

	private function logIn()
	{
	    $this->client->request('POST', '/login', [
	        'username' => 'john_admin',
	        'password' => 'test',
	        '_csrf_token' => $this->client->getContainer()->get('security.csrf.token_manager')->getToken('authenticate')->getValue(),
	    ]);
    
	    $this->assertEquals(302, $this->client->getResponse()->getStatusCode());
	    $this->assertEquals('/', $this->client->getResponse()->headers->get('location')); //success, we are inside of secured zone
	}

	private function logOut()
	{
	    $this->client->request('GET', '/logout');

	    $this->assertEquals(302, $this->client->getResponse()->getStatusCode());
	    $this->assertEquals('http://localhost/', $this->client->getResponse()->headers->get('location')); //redirect to homepage
	}
