<?php

namespace App\Service;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

/**
 * Envoie la fiche par e-mail à l'adresse paramétrée dans le backoffice.
 * Le contenu de la fiche n'existe qu'en mémoire le temps de la requête.
 */
class TransmissionMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly SettingsManager $settings,
        #[Autowire('%env(MAIL_FROM)%')]
        private readonly string $from,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @param UploadedFile[]       $attachments
     *
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */
    public function send(array $data, array $attachments = []): void
    {
        $data = $this->normalize($data);
        $service = $data['service'];

        $email = (new TemplatedEmail())
            ->from(new Address($this->from, 'Fiche de transmission IP'))
            ->to($this->settings->getRecipient())
            ->replyTo(new Address($service['courriel'], trim($service['prenom'].' '.$service['nom'])))
            // Pas de donnée nominative dans l'objet du message.
            ->subject('Fiche de transmission d’une information préoccupante – '.(new \DateTimeImmutable())->format('d/m/Y H:i'))
            ->htmlTemplate('email/transmission.html.twig')
            ->textTemplate('email/transmission.txt.twig')
            ->context(['d' => $data]);

        foreach ($attachments as $file) {
            $email->attachFromPath($file->getPathname(), $this->safeFilename($file), $file->getMimeType());
        }

        $this->mailer->send($email);
    }

    /**
     * Si l'anonymat est sollicité, l'identité de l'informateur est écartée côté serveur.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function normalize(array $data): array
    {
        if ($data['informateur']['anonyme'] ?? false) {
            $data['informateur'] = [
                'anonyme' => true,
                'qualite' => $data['informateur']['qualite'] ?? null,
                'qualite_autre' => $data['informateur']['qualite_autre'] ?? null,
            ];
        }

        return $data;
    }

    private function safeFilename(UploadedFile $file): string
    {
        $name = preg_replace('/[^A-Za-z0-9._-]+/', '_', $file->getClientOriginalName()) ?: 'piece-jointe';

        return mb_substr($name, 0, 100);
    }
}
