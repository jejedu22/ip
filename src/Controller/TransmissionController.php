<?php

namespace App\Controller;

use App\Entity\TransmissionLog;
use App\Form\TransmissionType;
use App\Service\TransmissionMailer;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

class TransmissionController extends AbstractController
{
    #[Route('/', name: 'transmission', methods: ['GET', 'POST'])]
    public function form(
        Request $request,
        TransmissionMailer $mailer,
        EntityManagerInterface $em,
        RateLimiterFactory $transmissionLimiter,
        LoggerInterface $logger,
    ): Response {
        $form = $this->createForm(TransmissionType::class, [
            'mineurs' => [[]],
            'autres_enfants' => [],
            'recueil' => ['date' => new \DateTimeImmutable('today')],
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            // Robot : le champ piège est rempli. On simule un succès sans rien envoyer.
            if (!empty($data['website'])) {
                return $this->redirectToRoute('transmission_merci');
            }

            if (!$transmissionLimiter->create($request->getClientIp() ?? 'unknown')->consume()->isAccepted()) {
                $this->addFlash('error', 'Trop d’envois depuis votre connexion. Merci de réessayer dans quelques minutes.');

                return $this->render('transmission/form.html.twig', ['form' => $form], new Response(status: 429));
            }

            try {
                $mailer->send($data, $data['pieces_jointes'] ?? []);
            } catch (TransportExceptionInterface $e) {
                // Le message d'erreur du transport ne contient pas le contenu de la fiche.
                $logger->error('Échec d’envoi d’une fiche de transmission.', ['exception' => $e::class]);
                $this->addFlash('error', 'L’envoi a échoué. Aucune donnée n’a été conservée : merci de réessayer ou de contacter directement la CRIP.');

                return $this->render('transmission/form.html.twig', ['form' => $form], new Response(status: 503));
            }

            // Seuls l'expéditeur et la date d'envoi sont conservés.
            $service = $data['service'];
            $em->persist(new TransmissionLog(trim($service['prenom'].' '.$service['nom']), $service['courriel']));
            $em->flush();

            return $this->redirectToRoute('transmission_merci');
        }

        return $this->render('transmission/form.html.twig', ['form' => $form], new Response(status: $form->isSubmitted() ? 422 : 200));
    }

    #[Route('/merci', name: 'transmission_merci', methods: ['GET'])]
    public function merci(): Response
    {
        return $this->render('transmission/merci.html.twig');
    }
}
