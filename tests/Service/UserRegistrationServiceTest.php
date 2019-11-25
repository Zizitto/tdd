<?php
namespace tests\Service;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\User\User;

class UserRegistrationServiceTest extends WebTestCase
{
    private $service = null;

    public function setUp()
    {
        self::bootKernel();
        $this->service = self::$kernel->getContainer()->get('userRegistrationServicePublicAlias');
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
