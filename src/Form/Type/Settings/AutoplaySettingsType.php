<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Form\Type\Settings;

use Vanssa\SyliusSliderPlugin\Preset\SettingsPresetProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class AutoplaySettingsType extends AbstractType
{
    public function __construct(
        private readonly SettingsPresetProvider $settingsPresetProvider,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $intervalValues = $this->settingsPresetProvider->values('slider', 'autoplay_interval', [2000, 3000, 5000, 8000, 10000]);
        $defaultInterval = (int) $this->settingsPresetProvider->safeDefault('slider', 'autoplay_interval', 5000, $intervalValues);

        $builder
            ->add('enabled', CheckboxType::class, [
                'required' => false,
                'help' => 'Automatically switch slides after the configured interval.',
            ])
            ->add('interval', ChoiceType::class, [
                'required' => false,
                'choices' => self::intervalChoices($intervalValues),
                'data' => $defaultInterval,
                'help' => 'Delay between automatic slide changes.',
                'constraints' => [
                    new Assert\Choice(['choices' => $intervalValues]),
                ],
            ])
            ->add('pauseOnHover', CheckboxType::class, [
                'required' => false,
                'help' => 'Pause autoplay while the cursor is over the slider.',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'empty_data' => function (): array {
                $intervalValues = $this->settingsPresetProvider->values('slider', 'autoplay_interval', [2000, 3000, 5000, 8000, 10000]);
                $defaultInterval = (int) $this->settingsPresetProvider->safeDefault('slider', 'autoplay_interval', 5000, $intervalValues);

                return ['enabled' => false, 'interval' => $defaultInterval, 'pauseOnHover' => true];
            },
            'allow_extra_fields' => true,
        ]);
    }

    /**
     * @param array<int, scalar> $values
     *
     * @return array<string, int>
     */
    private static function intervalChoices(array $values): array
    {
        $choices = [];
        foreach ($values as $value) {
            $milliseconds = (int) $value;
            if ($milliseconds <= 0) {
                continue;
            }

            $seconds = $milliseconds / 1000;
            $label = sprintf('%s seconds', rtrim(rtrim(number_format($seconds, 3, '.', ''), '0'), '.'));
            $choices[$label] = $milliseconds;
        }

        if ([] !== $choices) {
            return $choices;
        }

        return [
            '2 seconds' => 2000,
            '3 seconds' => 3000,
            '5 seconds' => 5000,
            '8 seconds' => 8000,
            '10 seconds' => 10000,
        ];
    }
}
