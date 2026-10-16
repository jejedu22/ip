<?php

namespace App\Repository;

use App\Entity\TransmissionLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TransmissionLog>
 */
class TransmissionLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TransmissionLog::class);
    }

    /**
     * @return TransmissionLog[]
     */
    public function findPage(int $page, int $perPage): array
    {
        return $this->createQueryBuilder('l')
            ->orderBy('l.sentAt', 'DESC')
            ->addOrderBy('l.id', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();
    }
}
