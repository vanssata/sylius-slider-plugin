# Extending Vanssa Sylius Slider Plugin

## Domain model

Main entities:

- `Vanssa\SyliusSliderPlugin\Entity\Slider`
- `Vanssa\SyliusSliderPlugin\Entity\Slide`
- `Vanssa\SyliusSliderPlugin\Entity\SliderTranslation`
- `Vanssa\SyliusSliderPlugin\Entity\SlideTranslation`

Slider ↔ Slide is many-to-many. Slider-local ordering is stored in `Slider.settings.slideOrder`.

## Common extension points

1. Add custom settings fields:
- Extend `SliderSettingsType` / `SlideSettingsType`.
- Persist to JSON settings arrays.
- Consume in Twig components or Stimulus controllers.

2. Extend admin UI:
- Add Twig Hook entries in `config/twig_hooks/admin/*.yaml`.
- Place templates in `templates/admin/...`.

3. Extend storefront rendering:
- Update Twig components in `src/Twig/Component/Shop/*`.
- Update templates under `templates/components/vanssa_sylius_slider/shop`.
- Add CSS/Stimulus logic in `assets/shop`.

4. Integrate external plugins:
- CMS: create reusable Twig partials for block rendering.
- Rich editor: detect class availability and switch form type.

## Data migration strategy

When changing settings schema:

- Keep backward compatibility in normalizers.
- Add migration for structural DB changes.
- Avoid breaking existing JSON keys without fallback logic.

## Fixture extension

- Add/modify fixture in `src/Fixture/SliderDemoFixture.php`.
- Keep media under `tests/TestApplication/public/media/fixtures`.
- Keep explicit license documentation for all fixture assets.
