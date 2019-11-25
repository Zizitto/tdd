## TDD of login process
### Planning
What we need to do?
We need to have secured page which will show some data and require user authentication:

Let`s split this task to few smaller tasks:
- Start new project
- Create page (homepage)
- Protect it with access policy
- Add login form
- Fill Homepage with some very secured content

# 1. New Project And Setup

- Install Symfony installer
	- curl -sS https://get.symfony.com/cli/installer | bash
	- mv ~/.symfony/bin/symfony /usr/local/bin/symfony

- Create project
	- symfony new tdd --full

- Open Project with PHPStorm
- Setup PHPStorm PHP and PHPUnit Configuration
    - Add PhpUnit configuration
        - change configuration file to use phpunit.xml.dist
    - PhpUnit Preferences (PhpStorm setting)
        - use bin/phpunit as phar

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

# 3. Protect it with login process

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

# 4. Add login form
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

# 5. Fill Homepage with some content.

### Write test:
Add new test file DefaultControllerServiceTest.php:

    <?php
        namespace tests\Service;
        
        use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
        
        class UserRegistrationServiceTest extends WebTestCase
        {
            private $client = null;
        
            public function setUp()
            {
                $this->client = static::createClient();
                $this->client->followRedirects(false);
            }
            
            ...
        }
Add test function to DefaultControllerServiceTest.php:

    
    public function testState1() {
        $userRegistrationService = $this->client->getContainer()->get(UserRegistrationService::class);

        $user = new User('test', 'test', ["ROLE_USER"]);
        $this->assertSame(1, $userRegistrationService->getState($user));
    }

### Run test:
    
    UserRegistrationService not found
    
### Fix test
    
- Add UserRegistrationService.php to Service directory


    <?php
    
    namespace App\Service;
    
    
    use Symfony\Component\Security\Core\User\User;
    
    class UserRegistrationService
    {
        
    }
    
    
- Declare service in config\packages\test\services.yml

    services:
        # default configuration for services in *this* file
        _defaults:
            autowire: true      # Automatically injects dependencies in your services.
            autoconfigure: true # Automatically registers your services as commands, event subscribers, etc.
        
        userRegistrationServicePublicAlias:
            class: App\Service\UserRegistrationService
            public: true

- Add Method to service which returns "1"


    public function getState(User $user) {
        return 1;
    }

### Run test:
    
    tests passed
    
### Add 2 more states tests

    
    public function testState2() {
        $userRegistrationService = $this->client->getContainer()->get(UserRegistrationService::class);

        $user = new User('test', 'password');
        $this->assertSame(2, $userRegistrationService->getState($user));
    }

    public function testState3() {
        $userRegistrationService = $this->client->getContainer()->get(UserRegistrationService::class);

        $user = new User('test', 'password', ["ROLE_USER"]);
        $this->assertSame(3, $userRegistrationService->getState($user));
    } 
    
 ### Run tests:
     
     testState2 and testState3 not passed
     
 ### Fix tests 
 
    public function getState(User $user) {
        $state = 3;

        if ($user->getRoles() == []) {
            $state = 2;
        }

        if ($user->getPassword() == null || $user->getPassword() == '') {
            $state = 1;
        }

        return $state;
    }

 ### Run tests:
     
     all tests passed
     
 ### Refactor tests
 
 Client is too big to save. Let`s try to avoid this and save only tested service
    
    class UserRegistrationServiceTest extends WebTestCase
    {
        private $service = null;
    
        public function setUp()
        {
            self::bootKernel();
            $this->service = self::$container->get('userRegistrationServicePublicAlias');
        }
    
        public function testState1() {
            $user = new User('test', '');
            $this->assertSame(1, $this->service->getState($user));
        }
    
        public function testState2() {
            $user = new User('test', 'password');
            $this->assertSame(2, $this->service->getState($user));
        }
    
        public function testState3() {
            $user = new User('test', 'password', ["ROLE_USER"]);
            $this->assertSame(3, $this->service->getState($user));
        }
    }

  ### Run tests
  
    tests passed
    
  ### Analyze tests performance
  
    https://monosnap.com/file/uD4f9IUi9FmCAC75yP1kf9gj8dfoZf
  
  ### Use our new service
  
  Change home controller action
  
      /**
       * Homepage
       *
       * @Route("/", name="home", methods={"GET"})
       * @param UserRegistrationService $registrationService
       * @return Response
       */
      public function homeAction(UserRegistrationService $registrationService) {
          return $this->render('home.html.twig', [
              'rating' => $registrationService->getState($this->getUser())
          ]);
      }
      
  Change template
  
  
      {% block body %}
          Very Secured data
          User rating: {{ rating }}
      {% endblock %}

# 6. Fill Homepage with some content.

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
	
Add test function to DefaultControllerTest.php:

	public function testHomePageContainsProfileLink() {
        $this->login();
        $crawler = $this->client->request('GET', '/');
    
        $this->assertEquals(1, $crawler->filter('a')->count());
    }
    
### Run test:
    1) tests\Controller\DefaultControllerTest::testHomePageContainsProfileLink
    Failed asserting that 0 matches expected 1.
### Fix test:
Add link to home.html.twig

    {% extends 'base.html.twig' %}
    
    {% block body %}
        Very Secured data
        <a href="{{ path('profile') }}">Profile</a>
    {% endblock %}

### Run tests
    OK (4 tests, 10 assertions)
    
Add test function to DefaultControllerTest.php:

	public function testHomePageClickProfileLink() {
        $this->login();
        $crawler = $this->client->request('GET', '/');
        $link = $crawler->filter('a')->link();
    
        $this->assertNotEmpty($link);
        $this->client->click($link);
    
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    }

### Run test:
    tests\Controller\DefaultControllerTest::testHomePageClickProfileLink
    Failed asserting that 404 matches expected 200.
    
### Fix test:
Add profile controller
    
    <?php
    
    namespace App\Controller;
    
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\Form\Extension\Core\Type\SubmitType;
    use Symfony\Component\Form\Extension\Core\Type\TextType;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Routing\Annotation\Route;
    use Symfony\Component\Validator\Constraints\Length;
    use Symfony\Component\Validator\Constraints\NotBlank;
    
    class ProfileController extends AbstractController
    {
        /**
         * @Route("/profile", name="profile", methods={"GET", "POST"})
         * @return Response
         */
        public function profileAction(Request $request)
        {
            $form = $this->createFormBuilder()
                ->add('username', TextType::class, [
                ])
                ->add('submit', SubmitType::class)
                ->getForm();
    
            $form->handleRequest($request);
    
            if ($form->isSubmitted() && $form->isValid()) {
                return $this->redirectToRoute('home');
            }
    
            return $this->render('profile.html.twig', [
                    'form' => $form->createView()
                ]
            );
        }
    }

### Run test:
    OK (5 tests, 14 assertions)
    
Add test function to ProfileControllerTest.php:

	public function testProfilePageIsFormAvailable() {
        $this->login();
        $crawler = $this->client->request('GET', '/profile');
    
        $this->assertContains('Profile page', $this->client->getResponse()->getContent());
    
        $form = $crawler->selectButton('Save')->form();
        $this->assertNotEmpty($form);
    }
    
### Run test

    tests\Controller\ProfileControllerTest::testProfilePageIsFormAvailable
    InvalidArgumentException: The current node list is empty.
    
### Fix test
    
    {% extends 'base.html.twig' %}
    
    {% block body %}
        Profile page
        {{ form(form) }}
    {% endblock %}
    
### Run tests

    OK (6 tests, 18 assertions)
    
Add tests for validation length username and form's submitting
    
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
    
### Run test

    tests\Controller\ProfileControllerTest::testProfilePageSubmitNotValidForm
    Failed asserting that 302 matches expected 200.

### Fix test 

Add constraint to profile form

    $form = $this->createFormBuilder()
        ->add('username', TextType::class, [
            'constraints' => [
                new NotBlank(), new Length(['max' => 6, 'min' => 2])
            ]
        ])
        ->add('submit', SubmitType::class, ['label' => 'Save'])
        ->getForm();
        
### Run test

    OK (8 tests, 28 assertions)
    
### Refactoring
assertContains can be replaced by assertSame with crawler get element text
