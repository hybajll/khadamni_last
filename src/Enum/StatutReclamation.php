<?php
namespace App\Enum;

enum StatutReclamation: string
{
    case EN_ATTENTE = 'EN_ATTENTE';
    case EN_COURS = 'EN_COURS';
    case RESOLUE = 'RESOLUE';
    case REJETEE = 'REJETEE';

    public function getLabel(): string
    {
        return match($this) {
            self::EN_ATTENTE => 'En attente',
            self::EN_COURS => 'En cours',
            self::RESOLUE => 'Résolue',
            self::REJETEE => 'Rejetée',
        };
    }

    public function getBadgeClass(): string
    {
        return match($this) {
            self::EN_ATTENTE => 'warning',
            self::EN_COURS => 'info',
            self::RESOLUE => 'success',
            self::REJETEE => 'danger',
        };
    }
}