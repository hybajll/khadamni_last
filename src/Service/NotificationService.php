<?php

namespace App\Service;

use App\Entity\Reclamation;
use App\Entity\ReponseReclamation;
use App\Repository\ReclamationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

class NotificationService {
    private $mailer;
    private $aiService;
    private $reclamationRepository;
    private $entityManager;

    public function __construct(
        MailerInterface $mailer, 
        AiAssistantService $aiService,
        ReclamationRepository $reclamationRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->mailer = $mailer;
        $this->aiService = $aiService;
        $this->reclamationRepository = $reclamationRepository;
        $this->entityManager = $entityManager;
    }

    public function generateAndStoreAiReply(Reclamation $reclamation, ?string $contextSolution = null): ReponseReclamation
    {
        // Génération du texte via Gemini
        $aiMessage = $this->aiService->generateAiResponse(
            $reclamation->getType()->value,
            $contextSolution
        );

        // Création de l'entité réponse
        $reply = new ReponseReclamation();
        $reply->setReclamation($reclamation);
        $reply->setMessage($aiMessage);
        $reply->setDateReponse(new \DateTime());
        $reply->setAuteur(null); // NULL = Assistant IA

        // Liaison bidirectionnelle pour l'affichage immédiat sans rechargement lourd
        $reclamation->addReponseReclamation($reply); 

        $this->entityManager->persist($reply);
        $this->entityManager->flush();

        return $reply;
    }

    public function sendStatusUpdateEmail(Reclamation $reclamation, ?string $aiMessage = null)
    {
        if (!$aiMessage) {
            $aiMessage = "Votre réclamation est en cours de traitement.";
        }

        $email = (new TemplatedEmail())
            ->from('support@khadamni.tn')
            ->to($reclamation->getUser()->getEmail())
            ->subject('Mise à jour de votre réclamation - Khadamni')
            ->htmlTemplate('emails/reclamation_status_update.html.twig')
            ->context([
                'reclamation' => $reclamation,
                'aiMessage' => $aiMessage,
            ]);

        $this->mailer->send($email);
    }
}