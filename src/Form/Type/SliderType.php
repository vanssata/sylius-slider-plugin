<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Vanssa\SyliusSliderPlugin\Entity\Slider;
use Vanssa\SyliusSliderPlugin\Form\Type\Settings\SliderSettingsType;
use Vanssa\SyliusSliderPlugin\Form\Type\Translation\SliderTranslationType;
use Sylius\Component\Core\Model\Channel;
use Sylius\Bundle\ResourceBundle\Form\Type\ResourceTranslationsType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class SliderType extends AbstractType
{
    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addCodeField($builder, false);

        $builder
            ->add('enabled', ChoiceType::class, [
                'required' => false,
                'expanded' => false,
                'choices' => [
                    'Yes' => true,
                    'No' => false,
                ],
                'label' => 'Enabled',
            ])
            ->add('channels', EntityType::class, [
                'class' => Channel::class,
                'choice_label' => 'name',
                'required' => false,
                'multiple' => true,
                'mapped' => false,
                'label' => 'Channels',
                'help' => 'Leave empty to display this slider on every channel.',
                'autocomplete' => true,
            ])
            ->add('settings', SliderSettingsType::class, [
                'required' => false,
            ])
            ->add('translations', ResourceTranslationsType::class, [
                'entry_type' => SliderTranslationType::class,
                'label' => false,
                'by_reference' => false,
            ])
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $slider = $event->getData();

            $this->addCodeField(
                $event->getForm(),
                $slider instanceof Slider && null !== $slider->getId(),
            );

            if (!$slider instanceof Slider) {
                return;
            }

            $codes = $slider->getChannelCodes();
            if ([] === $codes) {
                return;
            }

            $repository = $this->managerRegistry->getRepository(Channel::class);
            $channels = $repository->findBy(['code' => $codes]);
            $event->getForm()->get('channels')->setData($channels);
        });

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event): void {
            $slider = $event->getData();
            if (!$slider instanceof Slider) {
                return;
            }

            if ('' === trim($slider->getName())) {
                $slider->setName($slider->getCode());
            }

            $form = $event->getForm();
            $channels = $form->get('channels')->getData();
            $codes = [];
            if (is_iterable($channels)) {
                foreach ($channels as $channel) {
                    if ($channel instanceof Channel && '' !== $channel->getCode()) {
                        $codes[] = $channel->getCode();
                    }
                }
            }

            $slider->setChannelCodes($codes);
        });
    }

    private function addCodeField(FormBuilderInterface|FormInterface $form, bool $disabled): void
    {
        $form->add('code', TextType::class, [
            'label' => 'sylius.ui.code',
            'disabled' => $disabled,
            'constraints' => [
                new Assert\NotBlank(),
                new Assert\Length(['max' => 64]),
                new Assert\Regex([
                    'pattern' => '/^[A-Za-z0-9][A-Za-z0-9_-]*$/',
                    'message' => 'Code may contain only letters, numbers, dashes and underscores.',
                ]),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Slider::class,
            'allow_extra_fields' => true,
        ]);
    }
}
