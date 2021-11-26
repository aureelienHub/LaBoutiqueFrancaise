<?php

namespace App\Controller;

use App\Classe\Mail;
use App\Entity\User;
use DateTimeImmutable;
use App\Entity\ResetPassword;
use App\Form\ResetPasswordType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ResetPasswordController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/mot_de_passe_oublie", name="reset_password")
     */
    public function index(Request $request): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('home');
        }

        if ($request->get('email')) {
            $user = $this->entityManager->getRepository(User::class)->findOneByEmail($request->get('email'));

            if ($user) {
                $resetPassword = new ResetPassword();
                $resetPassword->setUser($user);
                $resetPassword->setToken(uniqid());
                $resetPassword->setCreatedAt(new DateTimeImmutable());

                $this->entityManager->persist($resetPassword);
                $this->entityManager->flush();

                $url = $this->generateUrl(
                    'update_password',
                    [
                        'token' => $resetPassword->getToken()
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );

                $content = 'Bonjour ' . $user->getFirstname() . ',<br> Vous avez demandé a réinitialiser votre mot de passe.<br><br>';
                $content .= 'Merci de cliquer sur le lien suivant pour <a href="' . $url . '">mettre à jour votre mot de passe</a>';

                $mail = new Mail();
                $mail->send($user->getEmail(), $user->getFirstname() . ' ' . $user->getLastname(), 'Réinitialiser mon mot de passe', $content);

                $this->addFlash('notice', 'Un mail vous à été envoyé pour mettre à jour le mot de passe');
            } else {
                $this->addFlash('notice', 'Cette adresse email n\'existe pas');
            }
        }


        return $this->render('reset_password/reset.html.twig');
    }

    /**
     * @Route("/modifier_mon_mot_de_passe/{token}", name="update_password")
     */
    public function update($token, Request $request, UserPasswordHasherInterface $hash): Response
    {
        $resetPassword = $this->entityManager->getRepository(ResetPassword::class)->findOneByToken($token);

        if (!$resetPassword) {
            return $this->redirectToRoute('reset_password');
        }

        $now = new DateTimeImmutable();

        if ($now > $resetPassword->getCreatedAt()->modify('+3 hour')) {
            $this->addFlash('notice', 'Votre demande de mot de passe à expiré. Merci de la renouveler');

            return $this->redirectToRoute('reset_password');
        }

        $form = $this->createForm(ResetPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $newPassword = $form->get('new_password')->getData();

            $password = $hash->hashPassword($resetPassword->getUser(), $newPassword);

                $resetPassword->getUser()->setPassword($password);

                $this->entityManager->flush();

                $this->addFlash('notice', 'Votre mot de passe à bien été mise à jour');

                return $this->redirectToRoute('app_login');
        }

        return $this->render('reset_password/update.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
