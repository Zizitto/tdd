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
