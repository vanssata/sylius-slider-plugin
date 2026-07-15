<?php

/*
 * Rector set for upgrading a project that used pre-2.2 releases of this
 * plugin (Acme\SyliusSliderPlugin namespace era) to the 2.2 line.
 *
 * Usage from your store project:
 *
 *   vendor/bin/rector process src \
 *       --config vendor/vanssa/sylius-slider-plugin/rector/sets/slider-plugin-2-2.php
 */

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\Name\RenameClassRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(RenameClassRector::class, [
        'Acme\\SyliusSliderPlugin\\AcmeSyliusSliderPlugin' => 'Vanssa\\SyliusSliderPlugin\\VanssaSyliusSliderPlugin',
        'Acme\\SyliusSliderPlugin\\Entity\\Slider' => 'Vanssa\\SyliusSliderPlugin\\Entity\\Slider',
        'Acme\\SyliusSliderPlugin\\Entity\\Slide' => 'Vanssa\\SyliusSliderPlugin\\Entity\\Slide',
        'Acme\\SyliusSliderPlugin\\Entity\\SliderTranslation' => 'Vanssa\\SyliusSliderPlugin\\Entity\\SliderTranslation',
        'Acme\\SyliusSliderPlugin\\Entity\\SlideTranslation' => 'Vanssa\\SyliusSliderPlugin\\Entity\\SlideTranslation',
        'Acme\\SyliusSliderPlugin\\Repository\\SliderRepository' => 'Vanssa\\SyliusSliderPlugin\\Repository\\SliderRepository',
        'Acme\\SyliusSliderPlugin\\Repository\\SlideRepository' => 'Vanssa\\SyliusSliderPlugin\\Repository\\SlideRepository',
        'Acme\\SyliusSliderPlugin\\Factory\\SlideFactory' => 'Vanssa\\SyliusSliderPlugin\\Factory\\SlideFactory',
        'Acme\\SyliusSliderPlugin\\Form\\Type\\SliderType' => 'Vanssa\\SyliusSliderPlugin\\Form\\Type\\SliderType',
        'Acme\\SyliusSliderPlugin\\Form\\Type\\SlideType' => 'Vanssa\\SyliusSliderPlugin\\Form\\Type\\SlideType',
        'Acme\\SyliusSliderPlugin\\Service\\UploadedMediaStorage' => 'Vanssa\\SyliusSliderPlugin\\Service\\UploadedMediaStorage',
        'Acme\\SyliusSliderPlugin\\Twig\\SliderExtension' => 'Vanssa\\SyliusSliderPlugin\\Twig\\SliderExtension',
    ]);
};
