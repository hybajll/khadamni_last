<?php

namespace App\Repository;

use App\Entity\SmsLog;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SmsLog>
 */
class SmsLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SmsLog::class);
    }

    /**
     * Vérifie si un SMS de rappel d'expiration a déjà été envoyé
     * pour un utilisateur et une date d'expiration donnée.
     * → évite les doublons si le cron tourne plusieurs fois.
     */
    public function hasAlreadySent(User $user, string $type, \DateTimeImmutable $endDate): bool
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.user = :user')
            ->andWhere('s.type = :type')
            ->andWhere('s.subscriptionEndDate = :endDate')
            ->andWhere('s.success = true')
            ->setParameter('user', $user)
            ->setParameter('type', $type)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    /**
     * Historique SMS d'un utilisateur.
     *
     * @return SmsLog[]
     */
    public function findByUser(User $user, int $limit = 20): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.user = :user')
            ->setParameter('user', $user)
            ->orderBy('s.sentAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
