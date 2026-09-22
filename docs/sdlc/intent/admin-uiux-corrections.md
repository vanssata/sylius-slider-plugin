# Intent: Admin UI/UX corrections found in a browser audit

<!-- Stage 1 of the SDLC flow. Say WHAT and WHY. Leave HOW to the spec. -->

## Problem

A browser audit of the plugin's admin surface (`http://sylius-slider.localhost/admin/`, Sylius 2.2.7
/ Symfony 7.4.14, branch `2.3`, stack in `dev`) walked every action reachable from the Sliders grid,
the slider workspace, the slide panels, the Style Presets grids and the preset forms, with particular
attention to **what happens when elements change dynamically** — live preview re-render, modals,
LiveComponent updates, drag reorder, AJAX panels.

The audit was analysis only; no repository file was changed and nothing was created or saved. Every
item below was observed in a real browser and is listed with the evidence that produced it. Each was
put to the product owner in turn; this document records **only the corrections that were approved**.

### Evidence of what hurts today

**Saving and losing work**

- There is no unsaved-changes guard anywhere: `window.onbeforeunload` is absent, no
  `data-turbo-confirm`, no dirty indicator. Editing the slider name and clicking a sidebar link
  navigates away silently and the edit is gone. On a form with **1771 form controls** this is a real
  loss of work.
- The submit button has no pending state — no disable, no spinner, no wiring — so a slow save on a
  1.1 MB form can be submitted twice.
- The same submit for `form="slider"` is rendered twice with two different labels: **"Update"** in the
  page header and **"Save changes"** in the settings drawer. Across the admin the same act of saving
  is spelled four ways: *Update*, *Save changes*, *Create*, *Add*.
- Reorder, add and remove act immediately and server-side, while settings changes wait for a submit —
  two different save models in one screen, with no explanation of which is which.

**Creating a slide (the sharpest defect found)**

- The create-slide modal loads **1078 form controls** behind a lazy turbo-frame.
- Submitting with the required `slide[code]` empty leaves the modal open with no visible change:
  `{stillOpen: true, scrollAfter: 0, invalid: ["slide", "slide[code]"], errs: ["This value should not
  be blank."], activeEl: "DIV"}`. The only error message sits at `codeTopInBody: 2967` inside a
  `scrollHeight: 3355` / `clientHeight: 786` body — no scroll into view, no focus move, no error
  summary. To the admin, "Create" simply does nothing.
- The first field in the form is `slide[settings][parallax][strength]`; identity (Name, Code) is far
  below the settings.
- `slide[position]` is required and pre-filled with `0` on a slider that already has four slides.
- `slide[code]` is required and empty, with no slug derived from the name.
- The modal contains **54 `<label>` elements with no text**.

**Presets — two different things with one name**

- The slider workspace renders **42 preset buttons** (6 slider presets, 9 slide presets × 4 slides).
  They are built-in and carry their code inline (`data-vanssa-preset-applier-preset-param="classic_arrows"`).
- The admin menu entries *Slider Presets* and *Slide Presets* point at
  `/admin/style-presets/?criteria[type]=slider|slide` — a separate, DB-backed store. Both land on
  **"No results found — Adjust your search and try again"**, as does the unfiltered grid. The filter
  values are correct (`slider`, `slide`); the store is simply empty.
- `/admin/sliders/new/from-preset/classic_arrows` returns **404**, and no page in the admin links to
  that route. The "create a slider from a preset" feature is unreachable.
- Applying a preset is a **partial merge with no reset**: `maxHeight=320px` set by *Compact Banner*
  survived into *Minimal Fade* and back into *Classic Arrows*
  (`{maxHeight:"320px", paginationStyle:"dots", effect:"slide"}`), and lives in the Settings drawer
  where the admin never sees it. Field coverage differs per preset: 23 keys exist, `classic_arrows`
  sets 17, `minimal_fade` 11, `autoplay_showcase` 13, `fullscreen_hero` 12, `compact_banner` 14,
  `parallax_showcase` 11.
- Hovering a preset renders a full server-side preview with no debounce and no abort: 6 hovers at
  40 ms spacing produced 6 complete renders (`{before: 2, after: 8, fired: 6, hovered: 6}`). Nothing
  on screen says the preview is temporary.
- The slide preset menu is duplicated once per slide (`dupSlidePresetMenus: 4`).
- The `style_preset` form's heading renders an untranslated key: **"New
  vanssa_sylius_slider.ui.style_preset"**.
- `style_preset[settings]` is a raw JSON textarea of dot-paths. Invalid JSON returns only **"This
  value is not valid."**, with the field 1077 px below a `scrollY: 0` viewport and focus left on
  `BODY` — though the page, unlike the modal, does show a top-level "This form contains errors."

**Page weight**

- One slider with four slides renders **1771 form controls**, **108 colour pickers**, **11 474 DOM
  nodes** and **1.1 MB of HTML**.
- **680** of those controls are translation fields across 8 locales, because non-translatable style
  settings are rendered per locale — `slider[translations][de_DE][settings][base][containerWidth]`,
  `…[maxHeight]`, `…[marginTop]` and so on. The cost scales linearly with slides and locales.
- Only `vanssa-slide-create-panel-1` is a lazy turbo-frame; every `vanssa-slide-edit-panel-N` and
  `vanssa-slide-preview-frame-N` is eager with no `src` and 0 inputs.
- The responsive breakpoint text is emitted three times per slide; the tablet and mobile copies are
  `display: none`, so this is markup weight rather than a screen-reader problem.
- 24 identical `.alert alert-info` helper boxes repeat the same text.

**Dynamic behaviour, accessibility and semantics**

- Drag reorder only accepts a drop on the `⋮⋮` handle; a drop on the row body is silently ignored.
  There is no keyboard alternative, no `role="list"`/`listitem`, no unique accessible names on the
  repeated row buttons, and the preview is not refreshed after a reorder.
- The fullscreen toggle has no `aria-pressed`, its name never changes ("Toggle fullscreen workspace",
  via `title` only, no `aria-label`), and entering fullscreen force-opens the Settings drawer while
  leaving force-closes it — discarding the admin's own choice
  (`initial {fs:false,drawer:false}` → `click {fs:true,drawer:true}` → `esc {fs:false,drawer:false}`).
- The preview toolbar is marked up as a `tablist` named "Preview toolbar" — the wrong role for a
  group of viewport toggles. The viewport group reads as a device switch when it actually changes the
  editing context, has no `aria-pressed`, and is ordered inconsistently.
- The language `<select>` above the preview has no `name` and no `id` and sits outside `#slider`.
  Switching locale fires exactly one preview request and does not disturb the form — but it changes
  only the *preview* language, the form still shows the original locale's fields, and a locale with no
  translation shows the English fallback with no indication that it is a fallback.
- Async regions carry no `aria-live` / `aria-busy`; result counts, pagination buttons and the grid's
  Edit/Delete icon buttons have no accessible names; the Enabled column is colour-only; sorting links
  carry noisy hrefs.
- The grid's empty state offers no "Clear search".
- The preview modal scrolls the whole dialog instead of the body, keeps `aria-hidden` on slides that
  are not genuinely hidden, autoplays in the admin, has no slider name in its title, and its mobile
  caption `line-height` does not follow `font-size`.
- The localStorage draft (`vanssa-slider-draft-1`) is a 68 446-character urlencoded form body. It is
  correctly cleared on page load, so the form and preview never desync — but booleans are dropped from
  it rather than sent explicitly, so an unchecked box is indistinguishable from an absent key.
- Smaller items: the stray `*` outside the General section body; no helper text under the disabled
  Code field; no clear way out of the Settings drawer; "Open full editor" does not look clickable; the
  slide modal has no slide name in its title, no in-form navigation, and is too narrow; the two
  preview endpoints disagree on their `locale` parameter.

**Confirmed not to be defects** (recorded so the spec does not chase them): hover preview restores the
previous state correctly and is keyboard-reachable through focus/blur; Esc exits the CSS
pseudo-fullscreen; there is exactly one Stimulus application and no double registration; the
`style_preset` Type select correctly shows and hides `slides`, `captureSlide` and `captureSlider` via
`vanssa-mockup-picker#refresh`; the mockup radios take their accessible names from wrapping labels;
the `_wdt` 404 in the console is the dev toolbar, not the plugin.

## Proposed outcome

### Saving

- One save model governs reorder, add, remove and settings. Every change is either deferred to an
  explicit save or applied immediately — not both in the same screen.
- Every control that saves the slider form is labelled **"Save changes"**. *Update*, *Create* and
  *Add* no longer name the same act.
- Leaving a page with unsaved changes warns the admin instead of discarding them silently.
- While there are unsaved changes, a visible indicator says so next to the save control.
- Submitting shows a pending state on the button; a second submit cannot be issued while the first is
  in flight.

### Creating a slide

- A failed submit is impossible to miss: an error summary at the top of the modal, the first invalid
  field scrolled into view, and focus moved to it.
- The form opens on identity (Name, Code); settings follow in collapsed sections or after creation.
- `position` is assigned automatically at the end of the list rather than being a required field
  pre-filled with `0`.
- `code` is derived from the name, and every `<label>` in the modal carries text.

### Presets

- The built-in presets are visible in the Style Presets grid (read-only, or copyable), so the menu
  entry never opens on an empty screen.
- The empty state explains that no custom presets exist yet and that the built-in ones live in the
  editor, and offers a way to create one — instead of "Adjust your search and try again".
- The two concepts are named differently in the UI, so "preset" is never ambiguous.
- "Create a slider from a preset" either works from a visible entry point with codes that resolve, or
  is removed.
- Applying a preset produces the same result regardless of what was applied before it: a preset either
  sets the full field set or explicitly resets the fields it does not set.
- Hovering a preset is debounced and aborts a superseded request; a visible indicator (with
  `aria-busy` / `aria-live`) says the preview is temporary.
- After applying a preset, an undo is offered.
- The slide preset menu exists once and is shared by all slides.
- The Style Preset form's heading is translated, and invalid JSON in `settings` says what is wrong and
  where, with the field scrolled into view and focused. The dot-path keys are edited through a
  supported editor rather than a raw textarea.

### Page weight

- Non-translatable style settings (`containerWidth`, `maxHeight`, `marginTop` and the rest) are
  rendered once, not once per locale.
- The form renders the active locale; the others load on demand.
- Changing the preview language also changes which translation fields the form shows.
- When the chosen locale has no translation, the preview says it is showing the default language.
- Slide panels load through lazy turbo-frames; colour pickers initialise on demand; the repeated
  breakpoint markup and the 24 duplicate helper boxes appear once.

### Dynamic behaviour, accessibility and semantics

- The whole row is a drop target for reorder, not only the `⋮⋮` handle.
- Reorder has a keyboard alternative and explicit Up/Down buttons, the list uses
  `role="list"`/`listitem`, every repeated row button has a unique accessible name, the preview
  refreshes after a reorder, and the admin gets visible feedback (toast, with undo).
- The fullscreen toggle exposes `aria-pressed`, changes its accessible name with its state, and leaves
  the Settings drawer exactly as the admin left it.
- The preview toolbar drops the `tablist` role. The viewport group is renamed to the editing context
  it actually controls, shows a visible indicator, exposes `aria-pressed`, and is ordered
  Desktop → Tablet → Mobile consistently.
- Async regions announce themselves through `aria-live` / `aria-busy`; result counts, pagination and
  the grid's Edit/Delete actions have accessible names and tooltips; the Enabled column carries text
  or a badge rather than colour alone; sorting links have clean hrefs; the empty state offers "Clear
  search".
- The preview modal scrolls in its body with a fixed footer, marks only genuinely hidden slides
  `aria-hidden`, does not autoplay in the admin, names the slider in its title, and scales its mobile
  caption line-height with the font size.
- The draft sends an explicit value for every boolean, **and** a missing boolean key is treated as
  `false` server-side. Types are normalised when the draft is rendered. The draft is bounded or
  compressed rather than carrying 68 KB.
- The slide modal names the slide in its title, offers navigation between sections, is wider or
  resizable, and "Open full editor" looks clickable. The stray `*` moves inside the General section
  body, the disabled Code field gains helper text, and the Settings drawer has a clear way out.
- The two preview endpoints agree on their `locale` parameter.

## Affected users & systems

- **Shop administrators** editing sliders, slides and presets — the only users of these screens.
- **Admin templates** under `templates/` (workspace, Twig components, Twig hooks) and the plugin's
  form types.
- **Stimulus controllers** `vanssa-preview-frame`, `vanssa-preset-applier`, `vanssa-slider-settings`,
  `vanssa-slider-slides-preview`, `vanssa-form-context`, `vanssa-modal-portal`,
  `vanssa-rgba-color-picker`, `vanssa-responsive-copy`, `vanssa-mockup-picker`, and the
  `vanssa_sylius_slider:admin:slider_slides_preview` LiveComponent.
- **Admin routes and controllers** `SliderPreviewController`, `SlidePreviewController`,
  `SlideEditPanelController`, `SlideCreatePanelController`, and the `from-preset` route.
- **The `StylePreset` resource**, its grid and its form, plus the built-in preset provider.
- **Translation catalogues** — new strings, and at least one missing key
  (`vanssa_sylius_slider.ui.style_preset`).
- **The storefront is not affected**; this is admin-only, though preset semantics and the draft
  payload touch what eventually renders in the shop.

## Constraints

- Sylius 2.x admin conventions and the existing grid, form and Twig-hook extension points stay intact;
  this is not a redesign of the workspace.
- Backward compatibility for a plugin consumer: public classes, form types, service ids, template
  block names and the Stimulus manifest are a published surface. Any change there goes through
  `sylius-bc-guard` before a tag.
- Adding or renaming a Stimulus controller means keeping five manifests in sync
  (`assets/package.json`, the three `controllers.json` files, and the Flex recipe) — see `CLAUDE.md`.
- Everything runs in containers; verification is `make verify`, `make behat` and `make e2e` /
  `make e2e-check`.
- The single-save-model decision supersedes the earlier suggestion to keep reorder instantaneous and
  merely explain it.
- Reducing per-locale settings changes the shape of persisted settings; migrating existing data must
  not lose a shop's current configuration. That makes this part a migration concern, not a template
  change.

## Out of scope

- The storefront rendering of sliders and slides.
- Sylius core admin defects: the sidebar search submit with no accessible name, and the "new version
  of Sylius available" banner.
- The `_wdt` 404 in the console — an artefact of running the stack in `dev`.
- Anything explicitly rejected during the audit: scaling the preview to fit, turning the preview into
  a fullscreen modal, moving the arrows outside the caption, reordering the modal's viewport tabs, a
  separate label for the modal's button, a selection counter, and "document it only" for page weight,
  the drop zone and the fullscreen coupling.
- Performance work beyond removing the duplicated markup and loading things on demand — no rewrite of
  the settings form architecture.

## Open questions

- **(blocking)** How are per-locale settings migrated once style settings stop being translatable —
  which locale wins, and what happens to shops that deliberately set different values per locale?
- **(blocking)** Should the built-in presets become real `StylePreset` records (seeded, then editable)
  or stay code-defined and be surfaced read-only? The answer decides whether `from-preset` is wired up
  or removed, and whether the two concepts can be merged instead of merely renamed.
- Which save model is correct for reorder — deferred with the rest of the form, or immediate with an
  undo — given that reorder currently posts to a LiveComponent
  (`POST /_components/vanssa_sylius_slider:admin:slider_slides_preview/reorderSlides`)?
- What replaces the raw JSON `settings` textarea: a generated field set from the known key reference,
  or a validated editor with autocomplete over dot-paths?
- Does the "create a slide" form stay one large modal with sections, or become a two-step flow
  (identity first, settings after creation)?
- The **"Save changes" round trip itself was never exercised** — the click was blocked as a mutating
  action, so the redirect target, the flash message and the post-save focus behaviour are still
  unverified and must be checked before the spec closes.

## Environment note

`browser_resize` initially failed with `Browser "chrome-for-testing" is not installed`. It was fixed
inside the container with:

```bash
docker compose --profile e2e exec -T -u root playwright sh -c 'npx -y @playwright/mcp@latest install-browser chrome-for-testing'
```

This is **lost on container rebuild** and has to be repeated before the next browser audit.
