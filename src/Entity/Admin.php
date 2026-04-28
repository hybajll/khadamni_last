<?php

namespace App\Entity;

use App\Repository\AdminRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdminRepository::class)]
class Admin extends User
{
    // Business roles stored in user.role (nullable for non-admins)
    public const BUSINESS_ROLE_SUPERADMIN = 'SUPERADMIN';
    public const BUSINESS_ROLE_MODERATOR = 'MODERATOR';
    public const BUSINESS_ROLE_MANAGER = 'MANAGER';

    public function isSuperAdmin(): bool
    {
        $role = strtoupper((string) $this->getAdminRole());
        return $role === self::BUSINESS_ROLE_SUPERADMIN || $role === 'SUPER_ADMIN';
    }
}
