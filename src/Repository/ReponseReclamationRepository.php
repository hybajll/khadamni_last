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

    /**
     * NOUVELLE MÉTHODE : Trouve les réponses récentes par type de réclamation
     */
    public function findRecentResponsesByType(string $typeReclamation, int $limit = 10): array
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.reclamation', 'rec')
            ->where('rec.type = :type')
            ->andWhere('r.message IS NOT NULL')
            ->andWhere('LENGTH(r.message) > 50') // Éviter les réponses trop courtes
            ->orderBy('r.date_reponse', 'DESC')
            ->setParameter('type', $typeReclamation)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * NOUVELLE MÉTHODE : Trouve les meilleures réponses d'admins
     */
    public function findBestResponsesByType(string $typeReclamation, int $limit = 5): array
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.reclamation', 'rec')
            ->leftJoin('r.auteur', 'admin')
            ->where('rec.type = :type')
            ->andWhere('r.message IS NOT NULL')
            ->andWhere('LENGTH(r.message) > 100') // Réponses substantielles
            ->andWhere('admin.id IS NOT NULL') // Réponses d'admins uniquement
            ->orderBy('r.date_reponse', 'DESC')
            ->setParameter('type', $typeReclamation)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
