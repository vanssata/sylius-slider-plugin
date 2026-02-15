<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Repository;

use Vanssa\SyliusSliderPlugin\Entity\Slider;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

class SliderRepository extends EntityRepository
{
    public function findEnabledOneByCodeForChannel(string $code, string $channelCode, string $locale, ?string $fallbackLocale = null): ?Slider
    {
        $slider = $this->findEnabledOneByCode($code);
        if (null === $slider) {
            return null;
        }

        return $slider->isAvailableForChannel($channelCode, $locale, $fallbackLocale) ? $slider : null;
    }

    public function findEnabledOneByCode(string $code): ?Slider
    {
        return $this->createQueryBuilder('slider')
            ->leftJoin('slider.slides', 'slide')
            ->addSelect('slide')
            ->andWhere('slider.code = :code')
            ->andWhere('slider.enabled = true')
            ->setParameter('code', $code)
            ->orderBy('slide.position', 'ASC')
            ->addOrderBy('slide.id', 'ASC')
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
