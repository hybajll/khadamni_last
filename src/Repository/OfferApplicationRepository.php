<?php

namespace App\Repository;

use App\Entity\Offer;
use App\Entity\OfferApplication;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OfferApplication>
 */
final class OfferApplicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OfferApplication::class);
    }

    public function findOneByOfferAndUser(Offer $offer, User $user): ?OfferApplication
    {
        return $this->findOneBy([
            'offer' => $offer,
            'user' => $user,
        ]);
    }
}

