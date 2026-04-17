<?php
namespace App\Enum;

enum TypeAuteur: string
{
    case USER = 'USER';
    case ADMIN = 'ADMIN';

    public function getLabel(): string
    {
        return match($this) {
            self::USER => 'Utilisateur',
            self::ADMIN => 'Administrateur',
        };
    }
}