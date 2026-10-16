<?php

namespace App\Controller;

use App\Entity\AdminUser;
use App\Form\PasswordChangeType;
use App\Form\SettingsType;
use App\Repository\TransmissionLogRepository;
use App\Service\SettingsManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    private const PER_PAGE = 25;

    #[Route('', name: 'admin_dashboard', methods: ['GET'])]
    public function dashboard(Request $request, TransmissionLogRepository $logs, SettingsManager $settings): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $total = $logs->count([]);

        return $this->render('admin/dashboard.html.twig', [
            'logs' => $logs->findPage($page, self::PER_PAGE),
            'total' => $total,
            'page' => $page,
            'pages' => max(1, (int) ceil($total / self::PER_PAGE)),
            'recipient' => $settings->getRecipient(),
        ]);
    }

    #[Route('/parametres', name: 'admin_settings', methods: ['GET', 'POST'])]
    public function settings(Request $request, SettingsManager $settings): Response
    {
        $form = $this->createForm(SettingsType::class, ['recipient' => $settings->getRecipient()]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $settings->setRecipient(mb_strtolower(trim($form->get('recipient')->getData())));
            $this->addFlash('success', 'L’adresse destinataire a été mise à jour.');

            return $this->redirectToRoute('admin_settings');
        }

        return $this->render('admin/settings.html.twig', ['form' => $form]);
    }

    #[Route('/mot-de-passe', name: 'admin_password', methods: ['GET', 'POST'])]
    public function password(Request $request, UserPasswordHasherInterface $hasher, EntityManagerInterface $em): Response
    {
        /** @var AdminUser $user */
        $user = $this->getUser();
        $form = $this->createForm(PasswordChangeType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($hasher->hashPassword($user, $form->get('new')->getData()));
            $em->flush();
            $this->addFlash('success', 'Votre mot de passe a été modifié.');

            return $this->redirectToRoute('admin_password');
        }

        return $this->render('admin/password.html.twig', ['form' => $form]);
    }
}
