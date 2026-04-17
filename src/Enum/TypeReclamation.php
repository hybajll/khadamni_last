<?php
namespace App\Enum;

enum TypeReclamation: string
{
    case PLATEFORME = 'PLATEFORME';
    case ENTREPRISE = 'ENTREPRISE'; // Renommé pour correspondre au label
    case STAGE = 'STAGE';           // Renommé pour correspondre au label
    case OFFRE_EMPLOIE = 'OFFRE_EMPLOIE';

    public function getLabel(): string
    {
        return match($this) {
            self::PLATEFORME => 'Plateforme',
            self::ENTREPRISE => 'Entreprise',
            self::STAGE => 'Stage',
            self::OFFRE_EMPLOIE => 'Offre d\'emploi',
        };
    }
}