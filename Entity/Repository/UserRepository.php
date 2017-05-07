<?php

namespace Ok99\PrivateZoneCore\UserBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Ok99\PrivateZoneCore\UserBundle\Entity\User;

class UserRepository extends EntityRepository
{
    /**
     * Returns active users query
     *
     * @return Query
     */
    public function getActiveUsersQuery()
    {
        $qb = $this->createQueryBuilder('u');
        return $qb
            ->where('u.regnum <= :maxRegnum')
            ->setParameter('maxRegnum', 9999)
            ->andWhere('u.enabled = :true')
            ->setParameter('true', true)
            ->orderBy('u.lastname', 'asc')
            ->addOrderBy('u.firstname', 'asc')
            ->getQuery();
    }

    /**
     * Returns active users
     *
     * @return User[]
     */
    public function getActiveUsers()
    {
        $query = $this->getActiveUsersQuery();
        try {
            return $query->getResult();
        } catch (NoResultException $e) {
            return null;
        }
    }

    /**
     * Returns users query
     *
     * @return Query
     */
    public function getUsersQuery()
    {
        $qb = $this->createQueryBuilder('u');
        return $qb
            ->where('u.regnum <= :maxRegnum')
            ->setParameter('maxRegnum', 9999)
            ->orderBy('u.lastname', 'asc')
            ->addOrderBy('u.firstname', 'asc')
            ->getQuery();
    }

    /**
     * Returns users
     *
     * @return User[]
     */
    public function getUsers()
    {
        $query = $this->getUsersQuery();
        try {
            return $query->getResult();
        } catch (NoResultException $e) {
            return null;
        }
    }

    /**
     * @param integer $ageMin
     * @param integer $ageMax
     * @return User[]
     */
    public function getActiveUsersByAge($ageMin, $ageMax)
    {
        if (!$ageMin) $ageMin = 0;
        if (!$ageMax) $ageMax = 999;

        $qb = $this->createQueryBuilder('u');
        $query = $qb
            ->where('u.regnum <= :maxRegnum')
            ->andWhere('u.enabled = :true')
            ->andWhere('YEAR(NOW()) - (FLOOR(u.regnum/100) + IF(FLOOR(u.regnum/100) < (YEAR(NOW()) - 2000), :century_21, :century_20)) >= :ageMin')
            ->andWhere('YEAR(NOW()) - (FLOOR(u.regnum/100) + IF(FLOOR(u.regnum/100) < (YEAR(NOW()) - 2000), :century_21, :century_20)) <= :ageMax')
            ->setParameter('maxRegnum', 9999)
            ->setParameter('true', true)
            ->setParameter('century_21', '2000')
            ->setParameter('century_20', '1900')
            ->setParameter('ageMin', $ageMin)
            ->setParameter('ageMax', $ageMax)
            ->orderBy('u.lastname', 'asc')
            ->addOrderBy('u.firstname', 'asc')
            ->getQuery();

        try {
            return $query->getResult();
        } catch (NoResultException $e) {
            return [];
        }
    }
}