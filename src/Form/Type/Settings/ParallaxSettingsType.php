<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Form\Type\Settings;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Vanssa\SyliusSliderPlugin\Preset\SettingsPresetProvider;

final class ParallaxSettingsType extends AbstractType
{
    public function __construct(
        private readonly SettingsPresetProvider $settingsPresetProvider,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $strengthValues = $this->settingsPresetProvider->values('slider', 'parallax_strength', ['0.5rem', '1rem', '2rem', '3rem', '4rem']);
        $choices = self::strengthChoices($strengthValues);
        if ($options['inherit']) {
            $choices = ['Disabled' => '0'] + $choices;
        }
        $strengthChoiceValues = array_values($choices);

        $builder
            ->add('strength', ChoiceType::class, [
                'choices' => $choices,
                'required' => false,
                'placeholder' => $options['inherit'] ? 'Inherit from slider' : 'Disabled',
                'help' => $options['inherit']
                    ? 'Parallax intensity for this slide. If empty, the slider setting applies; "Disabled" turns parallax off for this slide only.'
                    : 'Parallax intensity. If empty, parallax effect is disabled.',
                'constraints' => [
                    new Assert\Choice(['choices' => $strengthChoiceValues]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'empty_data' => static fn (): array => [
                'strength' => null,
            ],
            'inherit' => false,
        ]);

        $resolver->setAllowedTypes('inherit', 'bool');
    }

    /**
     * @param array<int, scalar> $values
     *
     * @return array<string, string>
     */
    private static function strengthChoices(array $values): array
    {
        $choices = [];
        foreach ($values as $value) {
            $valueString = (string) $value;
            $choices[$valueString] = $valueString;
        }

        return $choices;
    }
}
