<?php

namespace App\Repository;

use App\Entity\BookRating;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method BookRating|null find($id, $lockMode = null, $lockVersion = null)
 * @method BookRating|null findOneBy(array $criteria, array $orderBy = null)
 * @method BookRating[]    findAll()
 * @method BookRating[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BookRatingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BookRating::class);
    }

    /**
     * @return BookRating[] Returns an array of BookRating objects
     */
    public function getMostRatingBooks($limit)
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.rating > 0')
            ->orderBy('b.rating', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    /*
    public function findOneBySomeField($value): ?BookRating
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
