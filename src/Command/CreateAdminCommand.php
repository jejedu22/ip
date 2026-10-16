<?php

namespace App\Command;

use App\Entity\AdminUser;
use App\Repository\AdminUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(name: 'app:create-admin', description: 'Crée (ou réinitialise le mot de passe d\') un administrateur du backoffice')]
class CreateAdminCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AdminUserRepository $users,
        private readonly UserPasswordHasherInterface $hasher,
        private readonly ValidatorInterface $validator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED, 'Adresse e-mail (identifiant de connexion)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = mb_strtolower(trim((string) $input->getArgument('email')));

        if (count($this->validator->validate($email, new Email()))) {
            $io->error('Adresse e-mail invalide.');

            return Command::FAILURE;
        }

        $question = (new Question('Mot de passe (12 caractères minimum)'))
            ->setHidden(true)
            ->setValidator(function (?string $value): string {
                if (null === $value || mb_strlen($value) < 12) {
                    throw new \RuntimeException('12 caractères minimum.');
                }

                return $value;
            })
            ->setMaxAttempts(3);
        $password = $io->askQuestion($question);

        $user = $this->users->findOneBy(['email' => $email]);
        $created = null === $user;
        $user ??= (new AdminUser())->setEmail($email);
        $user->setPassword($this->hasher->hashPassword($user, $password));
        $this->em->persist($user);
        $this->em->flush();

        $io->success($created ? "Administrateur $email créé." : "Mot de passe de $email mis à jour.");

        return Command::SUCCESS;
    }
}
