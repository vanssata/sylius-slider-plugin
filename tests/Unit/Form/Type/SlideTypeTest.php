<?php

declare(strict_types=1);

namespace Tests\Vanssa\SyliusSliderPlugin\Unit\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vanssa\SyliusSliderPlugin\Entity\Slide;
use Vanssa\SyliusSliderPlugin\Form\Type\SlideType;
use Vanssa\SyliusSliderPlugin\Service\UploadedMediaStorage;
use Vanssa\SyliusSliderPlugin\Video\VideoProviderRegistry;
use Vanssa\SyliusSliderPlugin\Video\YouTubeVideoProvider;

final class SlideTypeTest extends TestCase
{
    public function testItConfiguresExpectedDefaults(): void
    {
        $type = new SlideType(
            new UploadedMediaStorage(sys_get_temp_dir()),
            $this->createMock(ManagerRegistry::class),
            new VideoProviderRegistry([new YouTubeVideoProvider()]),
        );

        $resolver = new OptionsResolver();
        $type->configureOptions($resolver);

        $options = $resolver->resolve();

        self::assertSame(Slide::class, $options['data_class']);
        self::assertTrue($options['allow_extra_fields']);
        self::assertSame(['data-controller' => 'slider-settings'], $options['attr']);
    }
}
