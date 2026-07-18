<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Form\Type\Settings;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Slide-global video behavior (not per breakpoint, not translated):
 * whether slide videos start on their own or wait for a visitor click.
 */
final class VideoSettingsType extends AbstractType
{
    public const PLAYBACK_AUTOPLAY = 'autoplay';

    public const PLAYBACK_CLICK = 'click';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('playback', ChoiceType::class, [
                'choices' => [
                    'Start automatically' => self::PLAYBACK_AUTOPLAY,
                    'Play button (visitor starts it)' => self::PLAYBACK_CLICK,
                ],
                'required' => false,
                'placeholder' => false,
                'empty_data' => self::PLAYBACK_AUTOPLAY,
                'help' => 'How slide videos start: automatically when the slide shows, or via a play button.',
                'constraints' => [
                    new Assert\Choice(['choices' => [self::PLAYBACK_AUTOPLAY, self::PLAYBACK_CLICK]]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'empty_data' => static fn (): array => [
                'playback' => self::PLAYBACK_AUTOPLAY,
            ],
        ]);
    }
}
