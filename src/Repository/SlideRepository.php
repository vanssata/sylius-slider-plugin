<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Repository;

use Vanssa\SyliusSliderPlugin\Entity\Slide;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

class SlideRepository extends EntityRepository
{
    public function findEnabledOneByCode(string $code): ?Slide
    {
        return $this->createQueryBuilder('slide')
            ->leftJoin('slide.sliders', 'slider')
            ->addSelect('slider')
            ->andWhere('slide.code = :code')
            ->andWhere('slide.enabled = true')
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
