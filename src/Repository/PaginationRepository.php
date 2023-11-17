<?php

namespace App\Repository;

use App\Entity\Pagination;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Pagination>
 *
 * @method Pagination|null find($id, $lockMode = null, $lockVersion = null)
 * @method Pagination|null findOneBy(array $criteria, array $orderBy = null)
 * @method Pagination[]    findAll()
 * @method Pagination[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PaginationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Pagination::class);
    }

    /**
    * @return Post[] Returns an array of Post objects
    */
    public function findAllPaginated(int $page): Paginator
    {
        $qb =  $this->createQueryBuilder('p')
            ->orderBy('p.publishedAt', 'DESC')            
        ;
        //Devolvemos los resutados de la página
        return (new Paginator($qb))->paginate($page);
    }


//    /**
//     * @return Pagination[] Returns an array of Pagination objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Pagination
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
