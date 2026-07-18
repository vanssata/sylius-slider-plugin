<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Twig\Admin;

use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Attribute\AsTwigFunction;

/**
 * Locale list for the workspace drawer's language switcher — the drawer is a
 * plain hook template (not a component), so it cannot inject the repository
 * itself.
 */
final readonly class AdminLocalesExtension
{
    /**
     * @param RepositoryInterface<LocaleInterface> $localeRepository
     */
    public function __construct(
        #[Autowire(service: 'sylius.repository.locale')]
        private RepositoryInterface $localeRepository,
    ) {
    }

    /**
     * @return array<int, LocaleInterface>
     */
    #[AsTwigFunction('vanssa_slider_admin_locales')]
    public function getLocales(): array
    {
        /** @var array<int, LocaleInterface> $locales */
        $locales = $this->localeRepository->findAll();

        return $locales;
    }
}
