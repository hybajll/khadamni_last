<?php

namespace App\Command;

use App\Service\SubscriptionReminderService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Commande quotidienne pour envoyer les rappels SMS J-2 avant expiration.
 *
 * ─── Lancement manuel ──────────────────────────────────────────────────────
 *   php bin/console app:send-subscription-reminder
 *   php bin/console app:send-subscription-reminder --dry-run   (simulation)
 *
 * ─── Cron job (chaque jour à 9h du matin) ──────────────────────────────────
 *   0 9 * * *  cd /var/www/khadamni && php bin/console app:send-subscription-reminder >> var/log/sms_reminder.log 2>&1
 * ────────────────────────────────────────────────────────────────────────────
 */
#[AsCommand(
    name:        'app:send-subscription-reminder',
    description: 'Envoie les SMS de rappel d\'expiration d\'abonnement (J-2) aux utilisateurs concernés.',
)]
class SendSubscriptionReminderCommand extends Command
{
    public function __construct(
        private readonly SubscriptionReminderService $reminderService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'dry-run',
            null,
            InputOption::VALUE_NONE,
            'Affiche les utilisateurs concernés sans envoyer de SMS ni créer de notifications.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io     = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');

        $io->title('📱 Rappels SMS — Expiration abonnement J-2');

        $targetDate = (new \DateTimeImmutable('+2 days'))->format('d/m/Y');
        $io->info("Date cible : abonnements expirant le {$targetDate}");

        if ($dryRun) {
            $io->warning('Mode simulation (dry-run) — aucun SMS ni notification ne sera créé.');
            $io->success('Simulation terminée. Relancez sans --dry-run pour envoyer réellement.');
            return Command::SUCCESS;
        }

        try {
            $io->text('Recherche des abonnements expirant dans 2 jours…');

            $result = $this->reminderService->processExpiringSubscriptions();

            $io->definitionList(
                ['✅ SMS envoyés'  => $result['sent']],
                ['⏭️  Ignorés (déjà envoyé ou sans téléphone)' => $result['skipped']],
                ['❌ Échecs'       => $result['failed']],
            );

            if ($result['failed'] > 0) {
                $io->warning("Certains SMS ont échoué. Vérifiez var/log/sms_reminder.log.");
            }

            $io->success(sprintf(
                'Traitement terminé : %d SMS envoyé(s), %d ignoré(s), %d échec(s).',
                $result['sent'],
                $result['skipped'],
                $result['failed'],
            ));

        } catch (\Throwable $e) {
            $io->error('Erreur critique : ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
