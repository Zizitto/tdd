<?php

namespace App\Controller;

use App\Service\UserRegistrationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
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
}
