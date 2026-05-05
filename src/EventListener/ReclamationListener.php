<?php
// src/EventListener/ReclamationListener.php

namespace App\EventListener;

use App\Entity\Reclamation;
use App\Service\NotificationService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: Reclamation::class)]
class ReclamationListener
{
    public function __construct(private NotificationService $notificationService) {}

    public function preUpdate(Reclamation $reclamation, PreUpdateEventArgs $event): void
    {
        // On vérifie si le champ 'statut' a été modifié
        if ($event->hasChangedField('statut')) {
            $this->notificationService->sendStatusUpdateEmail($reclamation);
        }
    }
}