<?php

namespace App\Controller;

use App\Form\ChangePasswordType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class AccountPasswordController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager) {

        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/compte/modifier-mot-de-passe", name="account_password")
     */
    public function index(Request $request, UserPasswordHasherInterface $hash): Response
    {
        $notification = null;

        $user = $this->getUser();
        $form = $this->createForm(ChangePasswordType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form ->isValid()) {

            $oldPassword = $form->get('old_password')->getData();

            if ($hash->isPasswordValid($user, $oldPassword)) {
                $newPassword = $form->get('new_password')->getData();
                $password = $hash->hashPassword($user, $newPassword);

                $user->setPassword($password);
                $this->entityManager->flush();

                $notification = 'Votre mot de passe à bien été mis à jour';
            }
            else {
                $notification = "Votre mot passe actuel est incorrect";
            }
        }

        return $this->render('account/password.html.twig', [
            'form' => $form->createView(),
            'notification' => $notification
        ]);
    }
}
