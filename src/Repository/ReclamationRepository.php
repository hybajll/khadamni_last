<?php
namespace App\Repository;

use App\Entity\Reclamation;
use App\Enum\StatutReclamation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ReclamationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reclamation::class);
    }

    public function findByUser(int $id): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.user = :userId')
            ->setParameter('userId', $id)
            ->orderBy('r.date_creation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByStatut(StatutReclamation $statut): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.statut = :statut')
            ->setParameter('statut', $statut)
            ->orderBy('r.date_creation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getStatistics(): array
    {
        // On crée une fonction rapide pour éviter de répéter le code
        $countByStatut = function($statut = null) {
            $qb = $this->createQueryBuilder('r')
                       ->select('COUNT(r.id_reclamation)'); // Correction du nom ici
            
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
}