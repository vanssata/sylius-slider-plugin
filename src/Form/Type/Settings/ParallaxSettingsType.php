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
        $strengthChoiceValues = array_map(static fn (mixed $value): string => (string) $value, $strengthValues);

        $builder
            ->add('strength', ChoiceType::class, [
                'choices' => self::strengthChoices($strengthValues),
                'required' => false,
                'placeholder' => 'Disabled',
                'help' => 'Parallax intensity. If empty, parallax effect is disabled.',
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
        ]);
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
