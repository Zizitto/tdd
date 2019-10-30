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
                'constraints' => [
                    new NotBlank(), new Length(['max' => 6, 'min' => 2])
                ]
            ])
            ->add('submit', SubmitType::class, ['label' => 'Save'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return $this->redirectToRoute('home', ['username' => $form->getData()['username']]);
        }

        return $this->render('profile.html.twig', [
                'form' => $form->createView()
            ]
        );
    }
}
