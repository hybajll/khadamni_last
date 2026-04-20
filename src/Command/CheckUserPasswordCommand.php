<?php

namespace App\Command;

use App\Entity\Society;
use App\Entity\User;
use App\Repository\SocietyRepository;
use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:check-password',
    description: 'Check if a plain password matches the stored user password.',
)]
final class CheckUserPasswordCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly SocietyRepository $societyRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'User email')
            ->addArgument('password', InputArgument::REQUIRED, 'Plain password to verify');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = (string) $input->getArgument('email');
        $plainPassword = (string) $input->getArgument('password');

        $user = $this->userRepository->findOneBy(['email' => $email]);
        if ($user instanceof User) {
            $isValid = $this->passwordHasher->isPasswordValid($user, $plainPassword);
            $output->writeln($isValid ? '<info>VALID (user)</info>' : '<error>INVALID (user)</error>');

            return $isValid ? Command::SUCCESS : Command::FAILURE;
        }

        $society = $this->societyRepository->findOneBy(['email' => $email]);
        if ($society instanceof Society) {
            $isValid = $this->passwordHasher->isPasswordValid($society, $plainPassword);
            $output->writeln($isValid ? '<info>VALID (society)</info>' : '<error>INVALID (society)</error>');

            return $isValid ? Command::SUCCESS : Command::FAILURE;
        }

        $output->writeln('<error>Account not found (user or society).</error>');

        return Command::FAILURE;
    }
}
