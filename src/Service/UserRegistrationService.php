<?php

namespace App\Service;


use Symfony\Component\Security\Core\User\User;

class UserRegistrationService
{
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
}
