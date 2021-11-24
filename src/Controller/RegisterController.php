<?php

namespace App\Controller;

use App\Classe\Mail;
use App\Entity\User;
use App\Form\RegisterType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegisterController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    /**
     * @Route("/inscription", name="register")
     */
    public function index(Request $request, UserPasswordHasherInterface $passwordHasher): Response
    {
        $notification = null;

        $user = new User();
        $form = $this->createForm(RegisterType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $user = $form->getData();

            $search_email = $this->entityManager->getRepository(User::class)->findOneByEmail($user->getEmail());

            if (!$search_email) {
                $hashedPassword = $passwordHasher->hashPassword($user, $user->getPassword());

                $user->setPassword($hashedPassword);

                $this->entityManager->persist($user);
                $this->entityManager->flush();

                $mail = new Mail();
                $content = 'Bonjour '.$user->getFirstName().'<br> Bienvenue sur la première boutique dédiée au made in France';
                $mail->send($user->getEmail(), $user->getFirstName(), 'Bienvenue sur la Boutique Francaise', $content);

                $notification = 'Votre inscription s\'est bien passé, vous pouvez vous connectez à votre compte. Un mail vous à été envoyé';
            } else {
                $notification = 'L\'email que vous avez renseigné existe déjà.';
            }
        }

        return $this->render('register/register.html.twig', [
            'form' => $form->createView(),
            'notification' => $notification
        ]);
    }
}
