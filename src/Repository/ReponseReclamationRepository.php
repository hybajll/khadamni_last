<?php

namespace App\Repository;

use App\Entity\ReponseReclamation;
use App\Entity\Admin;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ReponseReclamationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReponseReclamation::class);
    }

    public function findByReclamation(int $reclamationId): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.reclamation = :reclamationId')
            ->setParameter('reclamationId', $reclamationId)
            ->orderBy('r.date_reponse', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countUnansweredMessagesForAdmin(): int
    {
        $conn = $this->getEntityManager()->getConnection();

        // On joint la table user pour vérifier le discriminant 'type'
        // Les types 'etudiant' et 'diplome' sont considérés comme des utilisateurs
        $sql = '
            SELECT COUNT(*) as count 
            FROM (
                SELECT r1.id_reclamation
                FROM reponse_reclamation r1
                INNER JOIN user u ON r1.auteur_id = u.id
                WHERE r1.date_reponse = (
                    SELECT MAX(r2.date_reponse)
                    FROM reponse_reclamation r2
                    WHERE r2.id_reclamation = r1.id_reclamation
                )
                AND u.type IN ("etudiant", "diplome", "ETUDIANT", "DIPLOME")
            ) as unanswered
        ';

        $stmt = $conn->prepare($sql);
        return (int) $stmt->executeQuery()->fetchOne();
    }

    public function findRecentMessagesForAdmin(): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.reclamation', 'rec')
            ->join('r.auteur', 'a')
            ->where('a NOT INSTANCE OF :adminClass')
            ->andWhere('r.date_reponse > :date')
            ->setParameter('adminClass', $this->getEntityManager()->getClassMetadata(Admin::class))
            ->setParameter('date', new \DateTime('-60 minute')) 
            ->orderBy('r.date_reponse', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getLastResponseByReclamation(int $reclamationId): ?ReponseReclamation
    {
        return $this->createQueryBuilder('r')
            ->where('r.reclamation = :reclamationId')
            ->setParameter('reclamationId', $reclamationId)
            ->orderBy('r.date_reponse', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}