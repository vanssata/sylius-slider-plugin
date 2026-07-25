# Style presets

A style preset is a named bundle of slider or slide settings that you apply
to a form with one click. It fills fields; it does not create or save
anything by itself. Nothing is written to the database until you press
**Create** or **Update** on the form the preset filled.

Presets come in two flavours, and both appear side by side everywhere
presets are offered:

- **config** presets ship with the plugin (or are added by a developer in
  the project configuration). They are read-only in the admin.
- **custom** presets are rows you create yourself under *Slider Management*
  in the admin menu. They can be edited, disabled, reordered and deleted.

A custom preset whose **Code** matches a config preset replaces it: the
config version disappears from every gallery and dropdown for as long as
the custom row exists and is enabled.

For the configuration file format, see
[../dev/style-presets.md](../dev/style-presets.md).

## Where presets appear

There are three places in the admin:

| Place | How presets behave there |
| --- | --- |
| Preset gallery on the slider/slide **create** pages | Card grid, opens by itself once per page load |
| **Preset** dropdown in the preview toolbar | Hover to try on, click to apply |
| **Preset** dropdown in the in-modal slide creation form, reached from the *Slides* section of a slider's edit page | Click to apply (no hover try-on) |

Slide presets are offered on slide forms, slider presets on slider forms.
The two sets never mix.

## The preset gallery on create pages

Open *Slider Management -> Sliders -> Create*, or *Slider Management ->
Slides* and press **Create**. A modal titled **Start from a preset** opens
on its own.

![Preset gallery](../screenshots/admin-preset-gallery.png)

Each card shows the preset's mockup thumbnail, its label, and a badge that
reads `config` or `custom`. The first card is **Blank**, which closes the
modal and leaves the form at its defaults.

![Choosing a preset in the gallery](../media/preset-gallery.gif)

Clicking a preset card:

1. closes the modal,
2. writes the preset's values into the matching form fields,
3. leaves every field the preset does not mention untouched.

The form is not submitted. Review the result, fill in the parts a preset
cannot know (code, name, media, texts), and press **Create**.

If you closed the modal and want it back, use the **Choose preset** button
above the form.

Two flows differ slightly:

- On the **Slides** grid, the main **Create** button opens the gallery
  *before* the create form exists. Picking a card then navigates to the
  create page with `?preset=<code>` in the URL and applies the preset
  there. The **Blank** card goes straight to the plain create page.
- On the **Sliders** grid, **Create** goes straight to the create page and
  the gallery opens there.

### Slider presets that carry slides

A custom slider preset can list **source slides**. Its card in the gallery
does not fill the form: it opens
`/admin/sliders/new/from-preset/<code>` instead, and the server builds the
new slider with the preset's settings *and* a copy of each source slide
already attached.

The copies are independent. Editing a copied slide never changes the
preset's source slide, and editing the source never changes sliders that
were already created from the preset. Each copy gets its own code, derived
from the source code with a random suffix such as
`new-collection-copy-9f3a1c74`.

Config presets never carry slides, because only the database preset form
has a slides field.

Source slides are a gallery-only feature. The same preset picked from the
**Preset** dropdown on an existing slider applies its settings and nothing
else; no slides are copied there.

## Trying a preset on before applying it

Open a slider or slide for editing, or open the preview from the grid. The
preview toolbar has a **Preset** dropdown. Its header spells out the
interaction: *Hover to preview on the banner; click to apply, then Update*.

![Preset try-on](../media/preset-tryon.gif)

- **Hovering** (or focusing with the keyboard) a preset re-renders the
  preview with that preset applied on top of what you currently have. The
  form is not touched, and neither is the unsaved draft the preview uses.
  There is a short delay before the preview reloads, so moving quickly
  through the list does not trigger a render per entry.
- **Moving away** (or blurring) restores the preview to your actual
  current state.
- **Clicking** applies the preset to the real form fields. From that point
  it behaves like any other edit: the preview follows, and the change is
  only stored when you press **Update**.

Try-on never persists anything. If you hover a preset, like it, and then
navigate away without clicking and saving, nothing changed.

## What ships with the plugin

These presets are always available unless a developer replaces them in the
project configuration.

### Slide presets

| Code | Label | What it sets |
| --- | --- | --- |
| `hero_dark` | Hero Dark | White text, left-aligned, on a dark translucent panel (`rgba(15, 23, 42, 0.75)`), 2rem padding, 16px corners, 2.5rem headline, fade-up over 700 ms after a 100 ms delay, soft background blur |
| `clean_light` | Clean Light | Black text on a solid white panel placed a quarter in from the left, aligned to the bottom, fade-right over 500 ms |
| `minimal` | Minimal | No panel background at all; the media itself is darkened with `rgba(15, 23, 42, 0.75)`, white text bottom-left, no animation |
| `bold_center` | Bold Center | Centred block, 3rem headline in yellow (`rgba(250, 204, 21, 1)`) on `rgba(17, 24, 39, 0.9)`, medium background blur plus text blur, zoom-in over 700 ms, large primary button centred in the content |
| `split_left_light` | Split Left Light | Near-opaque white panel a third in from the left, dark text, fade-right over 600 ms |
| `gradient_overlay` | Gradient Overlay | Transparent panel, media darkened with `rgba(2, 6, 23, 0.55)`, white text bottom-left, square corners |
| `glass_card` | Glass Card | Centred translucent white panel (`rgba(255, 255, 255, 0.18)`) with text blur, 20px corners, zoom-in |
| `bottom_banner` | Bottom Banner | Full-width strip capped at 30% of the slide height, centred text on `rgba(2, 6, 23, 0.78)` |
| `promo_badge_right` | Promo Badge Right | Small badge near the top right, right-aligned, yellow headline on `rgba(15, 23, 42, 0.85)`, small primary button at the content's right edge |

All of them write into the **desktop** breakpoint only. Tablet and mobile
keep whatever you had. On the storefront tablet inherits every value it
does not override from desktop, and mobile inherits from tablet, so a
desktop-only preset still styles all three.

### Slider presets

| Code | Label | What it sets |
| --- | --- | --- |
| `classic_arrows` | Classic Arrows | Chevron arrows overlaid on the slides (3rem, soft shadow), round dots inside the bottom edge, slide transition at 500 ms, rewind on, autoplay off |
| `minimal_fade` | Minimal Fade | No arrows, line-style pagination, fade transition at 700 ms |
| `autoplay_showcase` | Autoplay Showcase | Autoplay every 5000 ms with pause on hover, progress bar on, arrows and dots |
| `fullscreen_hero` | Fullscreen Hero | Full-width container, fade at 800 ms, autoplay every 6000 ms with pause on hover, progress bar, line pagination, no arrows |
| `compact_banner` | Compact Banner | Content-width container capped at 320px, angle arrows placed outside the slides (1.5rem), numbered pagination below, autoplay off |
| `parallax_showcase` | Parallax Showcase | Parallax strength 2rem, overlaid arrows with a soft shadow, dots, slide transition at 600 ms |

The demo fixture suite (`make load-slider-fixtures`) creates one slider per
slider preset so you can compare them on the storefront:
`fashion-classic-arrows`, `fashion-minimal-fade`,
`fashion-autoplay-showcase`, `fashion-fullscreen-hero`,
`fashion-compact-banner` and `fashion-parallax-showcase`, each reachable at
`/slider/<code>` and `/banner/<code>`.

The demo slides are seeded from the slide presets in the same way:
`new-collection` from Gradient Overlay, `summer-dresses` from Clean Light,
`denim-essentials` from Split Left Light, `graphic-tees` from Bold Center,
`street-caps` from Bottom Banner, `season-sale` from Glass Card and
`runway-video` from Hero Dark.

## Managing your own presets

The admin menu **Slider Management** has two entries that lead to the same
**Style Presets** screen at `/admin/style-presets/`; they differ only in
the type they pre-filter on:

- **Slider Presets** filters the list to slider presets.
- **Slide Presets** filters the list to slide presets.

![Style presets list](../screenshots/admin-style-presets-index.png)

The list is empty on a fresh installation. The demo fixture suite does not
create any database presets, and the presets that ship with the plugin live
in configuration, not in this table. An empty list therefore does not mean
the galleries are empty.

Columns: mockup thumbnail, code, label, type, position and enabled state.
Filters: code, label, type (*Slide preset* / *Slider preset*) and enabled.
Row actions: update and delete. The list is sorted by position, ascending.

### The preset form

Press **Create** on the list, or open an existing row. The form has three
cards.

**Preset**

| Field | Notes |
| --- | --- |
| Code | Required, at most 64 characters, unique. Must start with a letter or digit and may then contain letters, digits, dashes and underscores. **Cannot be changed after the preset is created.** |
| Type | *Slide preset* or *Slider preset*. Decides which forms this preset is offered on. **Cannot be changed after the preset is created.** Opening the create page with `?type=slider` or `?type=slide` in the URL preselects it. |
| Label | Required, at most 255 characters. This is the name shown on the gallery card and in the dropdowns. |
| Position | Whole number, defaults to 0. Orders your presets among each other; config presets are always listed first. |
| Enabled | *Yes* or *No*. A disabled preset stays in this list but disappears from every gallery and dropdown immediately. |

Because code and type are locked after creation, a preset created with the
wrong type has to be deleted and recreated.

**Mockup**

The thumbnail that represents this preset on a gallery card and in the
grid. Seven images ship with the plugin: Dark, Light, With Text, Center
Bold, Minimal, Gradient and Glass. Pick one, or upload your own under
**Custom mockup image**; uploads are stored under
`public/media/slider/preset-mockups/`.

A preset without a mockup is not broken. Its card shows a neutral
placeholder icon instead. Of the presets that ship with the plugin, only
`bottom_banner` has no mockup assigned; that is the grey card in the
gallery screenshot near the top of this page.

**Preset settings**

| Field | Notes |
| --- | --- |
| Settings | The values this preset applies, as a flat JSON object. Invalid JSON is rejected when you save. The format is described in [../dev/style-presets.md](../dev/style-presets.md). |
| Capture settings from slide | Slide presets: leave the settings box empty and pick a slide here. On save, the preset is filled from that slide's current layout, linking and parallax settings. |
| Capture settings from slider | Slider presets: same idea, filled from the chosen slider's settings. Its channel restriction and slide order are left out, since those are properties of that one slider, not of a style. |
| Slides | Slider presets only: the slides copied into every new slider created from this preset. Ignored (and cleared on save) for slide presets. |

Capture only runs when the settings box is empty. If you both paste JSON
and pick a capture source, the JSON wins.

The intended way to build a preset without writing JSON: style one slide or
slider in the admin until it looks right, save it, then create a preset
with an empty settings box and that resource selected as the capture
source.

### Order and overriding

In every gallery and dropdown, presets appear in this order:

1. config presets, in the order they are defined in configuration,
2. then your database presets, by position ascending, then label
   alphabetically.

If a database preset reuses a config preset's code, it takes over that
config preset's slot in the list rather than being appended at the end.
Delete or disable the database row and the config preset comes back.

## Things that go wrong

- **"I applied a preset and nothing happened."** A preset only fills fields
  that exist on the open form. A slider preset applied to a slide form
  matches nothing, which is why the two sets are kept separate. Also check
  that the preset actually carries settings: a custom preset saved with an
  empty settings box and no capture source applies nothing.
- **"Only part of the preset came through."** A preset value that is not
  one of the options a dropdown offers cannot be selected, and that field
  is left blank instead. Developers can widen the available options; see
  [../dev/style-presets.md](../dev/style-presets.md).
- **"The preview changed but the form did not."** That is hover try-on.
  Click the entry to actually apply it, then press **Update**.
- **"My custom preset is not in the gallery."** Check *Enabled*, and check
  its *Type* against the form you are on.
- **"A config preset disappeared."** Some database preset is using the same
  code. Find it in the list by that code and delete or disable it.
- **"Editing a slide changed another slider."** Slides created from a
  slider preset are copies, so this cannot come from a preset. It means the
  same slide is attached to more than one slider.
