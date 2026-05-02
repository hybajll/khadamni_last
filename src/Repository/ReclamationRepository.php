<?php

namespace App\Repository;

use App\Entity\Reclamation;
use App\Enum\StatutReclamation;
use App\Enum\TypeReclamation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reclamation>
 */
class ReclamationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reclamation::class);
    }

    // --- RECHERCHE ET PAGINATION PAR ONGLET ---

    /**
     * Récupère les réclamations filtrées par onglet et éventuellement par date
     */
    public function findByTabAndDate(string $tab, ?\DateTimeInterface $date, int $limit, int $offset): array
    {
        $qb = $this->createQueryBuilder('r');

        // Filtrage par onglet : On vérifie si l'utilisateur est lié ou non
        if ($tab === 'user') {
            $qb->andWhere('r.user IS NOT NULL');
        } else {
            $qb->andWhere('r.user IS NULL');
        }

        // Filtrage par date (si une recherche est active)
        if ($date) {
            $qb->andWhere('r.date_creation LIKE :date')
               ->setParameter('date', $date->format('Y-m-d') . '%');
        }

        return $qb->orderBy('r.date_creation', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le total pour la pagination selon l'onglet et la date
     */
    public function countByTabAndDate(string $tab, ?\DateTimeInterface $date): int
    {
        $qb = $this->createQueryBuilder('r')
            ->select('COUNT(r.id_reclamation)');

        if ($tab === 'user') {
            $qb->andWhere('r.user IS NOT NULL');
        } else {
            $qb->andWhere('r.user IS NULL');
        }

        if ($date) {
            $qb->andWhere('r.date_creation LIKE :date')
               ->setParameter('date', $date->format('Y-m-d') . '%');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    // --- MÉTHODES EXISTANTES ET UTILITAIRES ---

    public function findByUser(int $id): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.user = :userId')
            ->setParameter('userId', $id)
            ->orderBy('r.date_creation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getStatistics(): array
    {
        $countByStatut = function($statut = null) {
            $qb = $this->createQueryBuilder('r')
                       ->select('COUNT(r.id_reclamation)');
            
            if ($statut) {
                $qb->where('r.statut = :s')
                   ->setParameter('s', $statut);
            }
            
            return $qb->getQuery()->getSingleScalarResult();
        };

        return [
            'total'      => $countByStatut(),
            'en_attente' => $countByStatut(StatutReclamation::EN_ATTENTE),
            'en_cours'   => $countByStatut(StatutReclamation::EN_COURS),
            'resolue'    => $countByStatut(StatutReclamation::RESOLUE),
            'rejetee'    => $countByStatut(StatutReclamation::REJETEE),
        ];
    }

    public function findSimilarByTypeWithResponse(TypeReclamation $type, int $currentId): ?Reclamation
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.reponseReclamations', 'rep')
            ->where('r.type = :type') 
            ->andWhere('r.id_reclamation != :currentId')
            ->andWhere('rep.message IS NOT NULL')
            ->setParameter('type', $type->value)
            ->setParameter('currentId', $currentId)
            ->orderBy('r.date_creation', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}