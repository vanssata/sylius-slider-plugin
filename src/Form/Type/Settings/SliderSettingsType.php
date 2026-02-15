<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Form\Type\Settings;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Sylius\Component\Core\Model\Channel;

final class SliderSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (true === $options['translation_mode']) {
            $builder->add('enabled', CheckboxType::class, [

                'label' => 'Use custom options for this locale',
                'attr' => [
                    'data-slider-settings-target' => 'translationEnabled',
                    'data-action' => 'change->slider-settings#translationEnabledChanged',
                ],
            ]);
        }

        $builder
            ->add('overlay', CheckboxType::class, ['required' => false])
            ->add('showTitle', CheckboxType::class, ['required' => false])
            ->add('containerWidth', ChoiceType::class, [
                'choices' => [
                    'Content width' => 'content',
                    'Full width' => 'full',
                ],

                'data' => 'full',
            ])
            ->add('marginTop', ChoiceType::class, [
                'choices' => self::spacingChoices(),
                'data' => '0'
            ])
            ->add('marginRight', ChoiceType::class, [
                'choices' => self::spacingChoices(),

                'data' => '0'

            ])
            ->add('marginBottom', ChoiceType::class, [
                'choices' => self::spacingChoices(),
                'data' => '0'

            ])
            ->add('marginLeft', ChoiceType::class, [
                'choices' => self::spacingChoices(),
                'data' => '0'

            ])
            ->add('paddingTop', ChoiceType::class, [
                'choices' => self::spacingChoices(),
                'data' => '0'

            ])
            ->add('paddingRight', ChoiceType::class, [
                'choices' => self::spacingChoices(),
                'data' => '0'

            ])
            ->add('paddingBottom', ChoiceType::class, [
                'choices' => self::spacingChoices(),
                'data' => '0'

            ])
            ->add('paddingLeft', ChoiceType::class, [
                'choices' => self::spacingChoices(),
                'data' => '0'

            ])
            ->add('justifySlideHeight', CheckboxType::class, ['required' => false])
            ->add('rewind', CheckboxType::class, ['required' => false])
            ->add('speed', IntegerType::class, ['required' => false])
            ->add('pauseOnHover', CheckboxType::class, ['required' => false])
            ->add('slideEffect', ChoiceType::class, [
                'choices' => [
                    'Slide' => 'slide',
                    'Fade' => 'fade',
                ],
                'data' => 'slide',
            ])
            ->add('cssClasses', TextType::class, ['required' => false])
            ->add('channelCodes', EntityType::class, [
                'class' => Channel::class,
                'choice_label' => 'name',
                'choice_value' => static function (mixed $choice): ?string {
                    if ($choice instanceof Channel) {
                        return $choice->getCode();
                    }

                    if (is_array($choice)) {
                        $code = $choice['code'] ?? null;

                        return is_string($code) ? $code : null;
                    }

                    return is_string($choice) ? $choice : null;
                },
                'multiple' => true,
                'autocomplete' => true,

                'help' => 'Leave empty to display on every channel.',
            ])
            ->add('autoplay', AutoplaySettingsType::class, [

            ])
            ->add('showNavigation', CheckboxType::class, ['required' => false])
            ->add('showArrows', CheckboxType::class, ['required' => false])
            ->add('navigationIcon', ChoiceType::class, [
                'choices' => [
                    'Chevron' => 'chevron',
                    'Angle' => 'angle',
                    'Square' => 'square',
                ],

                'data' => 'chevron',
            ])
            ->add('navigationSize', ChoiceType::class, [
                'choices' => [
                    'Compact (1.5 rem)' => '1.5rem',
                    'Default (3 rem)' => '3rem',
                    'Large (4 rem)' => '4rem',
                    'Extra large (6 rem)' => '6rem',
                ],

                'data' => '3rem',
            ])
            ->add('navigationColor', TextType::class, ['required' => false])
            ->add('navigationBackgroundColor', TextType::class, ['required' => false])
            ->add('navigationShadow', ChoiceType::class, [
                'choices' => [
                    'No shadow' => 'none',
                    'Soft shadow' => 'soft',
                    'Medium shadow' => 'medium',
                    'Strong shadow' => 'strong',
                    'Glow shadow' => 'glow',
                ],

                'data' => 'none',
            ])
            ->add('paginationShape', ChoiceType::class, [
                'choices' => [
                    'Circle' => 'circle',
                    'Square' => 'square',
                ],

                'data' => 'circle',
            ])
            ->add('paginationSize', ChoiceType::class, [
                'choices' => [
                    'Compact (0.5 rem)' => '0.5rem',
                    'Default (0.625 rem)' => '0.625rem',
                    'Large (0.8 rem)' => '0.8rem',
                    'Extra large (1 rem)' => '1rem',
                ],

                'data' => '0.625rem',
            ])
            ->add('paginationShadow', ChoiceType::class, [
                'choices' => [
                    'No shadow' => 'none',
                    'Soft shadow' => 'soft',
                    'Medium shadow' => 'medium',
                    'Strong shadow' => 'strong',
                    'Glow shadow' => 'glow',
                ],

                'data' => 'none',
            ])
            ->add('paginationColor', TextType::class, ['required' => false])
            ->add('paginationActiveColor', TextType::class, ['required' => false])
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $data = $event->getData();
            if (!is_array($data)) {
                return;
            }

            if (!isset($data['channelCodes']) || !is_array($data['channelCodes'])) {
                $data['navigationSize'] = self::normalizeNavigationSize($data['navigationSize'] ?? null);
                $data['paginationSize'] = self::normalizePaginationSize($data['paginationSize'] ?? null);
                $data['autoplay'] = self::normalizeAutoplay($data['autoplay'] ?? null);
                $event->setData($data);

                return;
            }

            $data['channelCodes'] = array_values(array_filter(array_map(static function (mixed $channel): ?string {
                if (is_string($channel) && '' !== $channel) {
                    return $channel;
                }

                if ($channel instanceof Channel) {
                    return $channel->getCode();
                }

                if (is_array($channel)) {
                    $code = $channel['code'] ?? null;

                    return is_string($code) && '' !== $code ? $code : null;
                }

                return null;
            }, $data['channelCodes'])));
            $data['navigationSize'] = self::normalizeNavigationSize($data['navigationSize'] ?? null);
            $data['paginationSize'] = self::normalizePaginationSize($data['paginationSize'] ?? null);
            $data['autoplay'] = self::normalizeAutoplay($data['autoplay'] ?? null);

            $event->setData($data);
        });

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event): void {
            $data = $event->getData();
            if (!is_array($data) || !isset($data['channelCodes']) || !is_array($data['channelCodes'])) {
                return;
            }

            $data['channelCodes'] = array_values(array_filter(array_map(static function (mixed $channel): ?string {
                if ($channel instanceof Channel) {
                    return $channel->getCode();
                }

                if (is_string($channel) && '' !== $channel) {
                    return $channel;
                }

                if (is_array($channel)) {
                    $code = $channel['code'] ?? null;

                    return is_string($code) && '' !== $code ? $code : null;
                }

                return null;
            }, $data['channelCodes'])));
            $data['navigationSize'] = self::normalizeNavigationSize($data['navigationSize'] ?? null);
            $data['paginationSize'] = self::normalizePaginationSize($data['paginationSize'] ?? null);
            $data['autoplay'] = self::normalizeAutoplay($data['autoplay'] ?? null);

            $event->setData($data);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'translation_mode' => false,
            'data_class' => null,
            'empty_data' => fn () => [
                'enabled' => false,
                'overlay' => false,
                'showTitle' => true,
                'containerWidth' => 'content',
                'marginTop' => '0',
                'marginRight' => '0',
                'marginBottom' => '0',
                'marginLeft' => '0',
                'paddingTop' => '0',
                'paddingRight' => '0',
                'paddingBottom' => '0',
                'paddingLeft' => '0',
                'justifySlideHeight' => true,
                'rewind' => true,
                'speed' => 400,
                'pauseOnHover' => true,
                'slideEffect' => 'slide',
                'cssClasses' => null,
                'channelCodes' => [],
                'autoplay' => ['enabled' => false, 'interval' => 5000, 'pauseOnHover' => true],
                'showNavigation' => true,
                'showArrows' => true,
                'navigationIcon' => 'chevron',
                'navigationSize' => '1.5rem',
                'navigationColor' => 'rgba(250, 204, 21, 1)',
                'navigationBackgroundColor' => 'rgba(17, 24, 39, 0.85)',
                'navigationShadow' => 'none',
                'paginationShape' => 'square',
                'paginationSize' => '0.625rem',
                'paginationShadow' => 'none',
                'paginationColor' => 'rgba(250, 204, 21, 0.45)',
                'paginationActiveColor' => 'rgba(250, 204, 21, 1)',
            ],
            'allow_extra_fields' => true,
            'attr' => [
                'data-controller' => 'slider-settings',
            ],
        ]);

        $resolver->setAllowedTypes('translation_mode', 'bool');
    }

    private static function normalizeNavigationSize(mixed $value): ?string
    {
        if (!is_string($value) || '' === $value) {
            return null;
        }

        return match ($value) {
            'sm' => '1rem',
            'md' => '1.5rem',
            'lg' => '2rem',
            default => $value,
        };
    }

    private static function normalizePaginationSize(mixed $value): ?string
    {
        if (!is_string($value) || '' === $value) {
            return null;
        }

        return match ($value) {
            'sm' => '0.5rem',
            'md' => '0.625rem',
            'lg' => '0.8rem',
            default => $value,
        };
    }

    private static function normalizeAutoplay(mixed $value): array
    {
        if (!is_array($value)) {
            return ['enabled' => false, 'interval' => 5000, 'pauseOnHover' => true];
        }

        $enabled = $value['enabled'] ?? $value['active'] ?? false;
        $interval = $value['interval'] ?? 5000;
        $pauseOnHover = $value['pauseOnHover'] ?? true;

        return [
            'enabled' => true === $enabled || 1 === $enabled || '1' === $enabled,
            'interval' => is_numeric($interval) ? (int) $interval : 5000,
            'pauseOnHover' => true === $pauseOnHover || 1 === $pauseOnHover || '1' === $pauseOnHover,
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function spacingChoices(): array
    {
        return [
            '0' => '0',
            '0.5 rem' => '0.5rem',
            '1 rem' => '1rem',
            '1.5 rem' => '1.5rem',
            '2 rem' => '2rem',
            '4 rem' => '4rem',
        ];
    }
}
