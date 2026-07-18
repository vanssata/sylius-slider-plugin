<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Repository;

use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Vanssa\SyliusSliderPlugin\Entity\StylePreset;

class StylePresetRepository extends EntityRepository
{
    /**
     * @return list<StylePreset>
     */
    public function findEnabledByType(string $type): array
    {
        /** @var list<StylePreset> $presets */
        $presets = $this->createQueryBuilder('preset')
            ->andWhere('preset.type = :type')
            ->andWhere('preset.enabled = true')
            ->setParameter('type', $type)
            ->orderBy('preset.position', 'ASC')
            ->addOrderBy('preset.label', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return $presets;
    }

    public function findEnabledOneByCodeAndType(string $code, string $type): ?StylePreset
    {
        $preset = $this->createQueryBuilder('preset')
            ->andWhere('preset.code = :code')
            ->andWhere('preset.type = :type')
            ->andWhere('preset.enabled = true')
            ->setParameter('code', $code)
            ->setParameter('type', $type)
            ->getQuery()
            ->getOneOrNullResult()
        ;
        \assert(null === $preset || $preset instanceof StylePreset);

        return $preset;
    }
}
