<?php

namespace ACSEO\TypesenseBundle\DataProvider;

use Doctrine\ORM\EntityManagerInterface;

readonly class EntityDataProvider implements DataProvider
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function getData(string $className,  int $page, int $maxPerPage): iterable
    {
        return $this->em->createQuery('select e from '.$className.' e')
            ->setFirstResult(($page - 1) * $maxPerPage)
            ->setMaxResults($maxPerPage)
            ->toIterable();
    }
}
