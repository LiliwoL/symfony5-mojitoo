<?php

namespace App\Repository;

use App\Entity\Places;
use App\Service\Radius;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

/**
 * @method Places|null find($id, $lockMode = null, $lockVersion = null)
 * @method Places|null findOneBy(array $criteria, array $orderBy = null)
 * @method Places[]    findAll()
 * @method Places[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PlacesRepository extends ServiceEntityRepository
{
    private $_containerBagInterface;

    public function __construct(ManagerRegistry $registry, ContainerBagInterface $containerBagInterface)
    {    
        parent::__construct($registry, Places::class);
        $this->_containerBagInterface = $containerBagInterface;
    }

    /**
      * @return Places[] Returns an array of Places objects
    */
    public function findByGeoLoc($lat, $lng) : array
    {
        // https://ourcodeworld.com/articles/read/1019/how-to-find-nearest-locations-from-a-collection-of-coordinates-latitude-and-longitude-with-php-mysql
        if ($this->_em->getConnection()->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SqlitePlatform)
        {
            // On ne peut pas utiliser les fonctions ACOS pour une recherche en RADIUS
            $latPlus = $lat + 1;
            $latMoins = $lat -1;

            $lngPlus = $lng + 1;
            $lngMoins = $lng -1;
            
            return $this->createQueryBuilder('p')
            ->andWhere('p.lng >= :lngMoins')            
            ->andWhere('p.lng <= :lngPlus')
            ->andWhere('p.lat >= :latMoins') 
            ->andWhere('p.lat <= :latPlus') 
            ->setParameter('latMoins', $latMoins)
            ->setParameter('latPlus', $latPlus)
            ->setParameter('lngMoins', $lngMoins)
            ->setParameter('lngPlus', $lngPlus)
            ->getQuery()
            ->getResult()
        ;
        }else{
            /**
             * @TODO: à tester
             */
            return $this->createQueryBuilder('p')
            ->select(
                        '(
                            (
                                acos(
                                    sin(( :lat * pi() / 180))
                                    *
                                    sin(( `lat` * pi() / 180)) + cos(( :lat * pi() /180 ))
                                    *
                                    cos(( `lat` * pi() / 180)) * cos((( :lng - `lng`) * pi()/180 ))
                                )
                            ) * 180/pi()
                        ) * 60 * 1.1515 * 1.609344'
                    )
            ->setParameter('lat', $lat)
            ->setParameter('lng', $lng)
            ->getQuery()
            ->getResult()
        ;
        }
    }

    /*
    public function findOneBySomeField($value): ?Places
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
