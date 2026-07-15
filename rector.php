<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests/Behat',
        __DIR__ . '/tests/Functional',
        __DIR__ . '/tests/Unit',
    ])
    ->withPhpVersion(PhpVersion::PHP_83)
    ->withPhpSets(php83: true)
    ->withSkip([
        // Sylius interfaces evolve between 2.1/2.2; #[\Override] would break the lower bound.
        AddOverrideAttributeToOverriddenMethodsRector::class,
        __DIR__ . '/src/Migrations',
    ])
    ->withImportNames(importShortClasses: false, removeUnusedImports: true);
