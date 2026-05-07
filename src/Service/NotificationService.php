<?php

namespace App\Service;

use App\Entity\Reclamation;
use App\Entity\ReponseReclamation;
use App\Enum\StatutReclamation;
use App\Repository\ReclamationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Psr\Log\LoggerInterface;

class NotificationService {
    private $mailer;
    private $aiService;
    private $reclamationRepository;
    private $entityManager;
    private $logger;

    public function __construct(
        MailerInterface $mailer, 
        AiAssistantService $aiService,
        ReclamationRepository $reclamationRepository,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ) {
        $this->mailer = $mailer;
        $this->aiService = $aiService;
        $this->reclamationRepository = $reclamationRepository;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    public function generateAndStoreAiReply(Reclamation $reclamation, ?string $contextSolution = null): ?ReponseReclamation
    {
        $aiMessage = $this->aiService->generateAiResponse(
            $reclamation->getType()->value,
            $contextSolution,
            $reclamation->getDescription()
        );

        $reply = new ReponseReclamation();
        $reply->setReclamation($reclamation);
        $reply->setMessage($aiMessage);
        $reply->setDateReponse(new \DateTime());
        $reply->setAuteur(null); 

        $reclamation->addReponseReclamation($reply); 
        $reclamation->setStatut(StatutReclamation::EN_COURS);

        $this->entityManager->persist($reply);
        $this->entityManager->flush();

        return $reply;
    }

    public function sendStatusUpdateEmail(Reclamation $reclamation, ?string $aiMessage = null): void
    {
        if (!$aiMessage) {
            $aiMessage = "Votre réclamation est en cours de traitement.";
        }

        $recipient = $reclamation->getUser() ?? $reclamation->getSociety();
        if (!$recipient) return;

        $displayName = ($recipient instanceof \App\Entity\Society) ? $recipient->getName() : $recipient->getNom();

        $email = (new TemplatedEmail())
            ->from('support@khadamni.tn')
            ->to($recipient->getEmail())
            ->subject('Khadamni - Assistance Automatique')
            ->htmlTemplate('emails/reclamation_status_update.html.twig')
            ->context([
                'reclamation' => $reclamation,
                'aiMessage' => $aiMessage,
                'recipientName' => $displayName,
            ]);

        try {
            $this->mailer->send($email);
        } catch (\Exception $e) {
            $this->logger->critical("Erreur Mailtrap : " . $e->getMessage());
        }
    }
}
