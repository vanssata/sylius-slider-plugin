import { expect, test, type Locator, type Page } from '@playwright/test';

import { expectNoA11yViolations } from '../support/a11y';
import { loginAsAdmin, openSettingsDrawer, slideIdByCode } from '../support/admin';
import { SLIDES, routes } from '../support/data';

/**
 * Two regressions, both on the slide edit workspace's live preview
 * (templates/admin/slide/form/workspace.html.twig), which shares its
 * `<turbo-frame>` with the admin page's own viewport — so CSS media queries
 * can never select a tablet/mobile media variant there.
 *
 * 1. `Slide::getLocalizedMediaGroups($locale, $fallback, $onlyBreakpoint)`
 *    now collapses to ONE breakpoint's effective media, driven by
 *    `SlideComponent::$previewBreakpoint` (set from the preview controller's
 *    `?breakpoint=` query param). Before this, switching the toolbar's
 *    resolution never changed which file rendered: both breakpoints showed
 *    the desktop cover, because the media-query-gated markup can't apply
 *    inside a same-viewport iframe. `denim-essentials` is the fixture slide
 *    with genuinely distinct desktop/mobile files (SliderDemoFixture uploads
 *    a different image per device), so a wrong implementation is directly
 *    observable as "the src never changes".
 *
 * 2. The media tile's × (templates/admin/shared/form/media_upload_field.html.twig
 *    + assets/admin/controllers/image_upload_preview_controller.js) flags an
 *    unmapped `slide[slideCover*Remove]` checkbox rather than deleting
 *    anything: SlidePreviewController::applyMediaRemovals reads it to null
 *    the field out in memory (never flushed) so the live preview reflects the
 *    removal before the form is ever saved, and Undo can restore both the
 *    flag and the thumbnail. An `.is-empty` tile (nothing stored, nothing
 *    picked) must not expose a × at all (media_tile.scss).
 *
 * Both tests are read-only: nothing is submitted, so the fixture dataset is
 * never touched and needs no restore step (verified below with a hard reload
 * after the remove/undo round-trip).
 */

/**
 * `<turbo-frame>` carries no role and no accessible name — its id is the
 * contract between the edit page and the preview response (both render
 * `vanssa-slide-preview-frame-{id}`), so it is the only stable hook here.
 */
function previewFrame(page: Page, slideId: string): Locator {
    return page.locator(`#vanssa-slide-preview-frame-${slideId}`);
}

/**
 * The one media element `previewBreakpoint` collapses the slide component to
 * (see Slide::getLocalizedMediaGroups) — never more than one on this page,
 * since the admin preview renders a single slide, not a carousel.
 */
function previewMedia(frame: Locator): Locator {
    return frame.locator('.vanssa-slide__media');
}

/** Defensive read: a breakpoint/draft switch swaps the frame's content, so a
 * read can land mid-swap. Returning null (rather than throwing) lets
 * `expect.poll` retry instead of failing on the transient gap. */
async function mediaSrc(frame: Locator): Promise<string | null> {
    try {
        return await previewMedia(frame).first().getAttribute('src');
    } catch {
        return null;
    }
}

async function switchBreakpoint(page: Page, name: 'Desktop' | 'Mobile' | 'Tablet'): Promise<void> {
    await page.getByRole('group', { name: 'Preview resolution' }).getByRole('button', { name }).click();
}

/**
 * `#vanssa-slide-media-settings` (templates/admin/slide/form/sections/media.html.twig)
 * is a plain Bootstrap card: its `<h2>` gives it an accessible NAME but the
 * card itself carries no landmark role, so there is no role query that scopes
 * to "the Media & Settings section" — the id is the only hook available.
 */
function mediaSettingsCard(page: Page): Locator {
    return page.locator('#vanssa-slide-media-settings');
}

/**
 * `data-vanssa-context-breakpoint` is form_context_controller.js's own
 * visibility marker (toggles `d-none` when the toolbar's breakpoint changes)
 * with no ARIA equivalent — screen readers just see whichever section is
 * currently not display:none, which is exactly what this selects.
 */
function mediaSection(page: Page, breakpoint: 'desktop' | 'tablet' | 'mobile'): Locator {
    return mediaSettingsCard(page).locator(`[data-vanssa-context-breakpoint="${breakpoint}"]`);
}

/**
 * The desktop breakpoint's IMAGE slot: `media_item()` (_breakpoint_settings.html.twig)
 * renders the image row before the video row, and `denim-essentials` has no
 * video (SliderDemoFixture), so this is also the section's only FILLED — and
 * therefore only reachable-by-role — remove control.
 */
function desktopImageTileRow(page: Page): Locator {
    return mediaSection(page, 'desktop').locator('[data-controller~="vanssa-image-upload-preview"]').first();
}

test.describe('admin slide editor: breakpoint-aware media preview', () => {
    test('switching the preview toolbar to Mobile swaps the rendered cover, not just the frame size', async ({ page }) => {
        await loginAsAdmin(page);
        const slideId = await slideIdByCode(page, SLIDES.denimEssentials);
        await page.goto(routes.adminSlideEdit(slideId));

        const frame = previewFrame(page, slideId);

        // Premise: a real single-slide render, complete — not an empty or
        // error frame.
        await expect(frame.getByRole('region', { name: 'Denim Essentials' })).toMatchAriaSnapshot(`
            - region "Denim Essentials":
              - article:
                - img "Denim Essentials"
                - heading "Denim Essentials" [level=3]
                - text: Jeans and shorts that go with everything you own.
        `);

        // Exactly one media element: `previewBreakpoint` collapses the
        // component's mediaGroups loop to one entry instead of one per
        // breakpoint.
        await expect(previewMedia(frame)).toHaveCount(1);
        await expect.poll(() => mediaSrc(frame)).toMatch(/^\/media\/fixtures\/desktop\//);

        await openSettingsDrawer(page);

        await switchBreakpoint(page, 'Mobile');
        await expect(frame).toHaveAttribute('src', /[?&]breakpoint=mobile$/);
        // The regression this guards: before the fix, BOTH breakpoints
        // rendered the desktop file, because the preview shares the admin
        // page's own viewport and no media query could ever select the
        // mobile variant there.
        await expect.poll(() => mediaSrc(frame)).toMatch(/^\/media\/fixtures\/mobile\//);

        await switchBreakpoint(page, 'Desktop');
        await expect(frame).toHaveAttribute('src', /[?&]breakpoint=desktop$/);
        await expect.poll(() => mediaSrc(frame)).toMatch(/^\/media\/fixtures\/desktop\//);
    });
});

test.describe('admin slide editor: media tile removal', () => {
    test('the × is only reachable on a filled slot', async ({ page }) => {
        await loginAsAdmin(page);
        const slideId = await slideIdByCode(page, SLIDES.denimEssentials);
        await page.goto(routes.adminSlideEdit(slideId));
        await openSettingsDrawer(page);

        // denim-essentials: a desktop cover but no video (SliderDemoFixture)
        // -> one empty, one filled tile, one reachable ×.
        const desktop = mediaSection(page, 'desktop');
        await expect(desktop.locator('.vanssa-media-tile.is-empty')).toHaveCount(1);
        await expect(desktop.getByRole('button', { name: 'Remove this media' })).toHaveCount(1);

        // No fixture slide ever sets a *Tablet cover (SliderDemoFixture only
        // uploads desktop/mobile) -> both tablet slots are empty, and an
        // empty tile renders no × at all (media_tile.scss:
        // `&.is-empty &__remove { display: none; }`), so the role query must
        // come back with nothing rather than a hidden node.
        await switchBreakpoint(page, 'Tablet');
        const tablet = mediaSection(page, 'tablet');
        await expect(tablet.locator('.vanssa-media-tile.is-empty')).toHaveCount(2);
        await expect(tablet.getByRole('button', { name: 'Remove this media' })).toHaveCount(0);
    });

    test('clicking × flags the slot and live-updates the preview; Undo restores both', async ({ page }) => {
        await loginAsAdmin(page);
        const slideId = await slideIdByCode(page, SLIDES.denimEssentials);
        await page.goto(routes.adminSlideEdit(slideId));

        const frame = previewFrame(page, slideId);
        await expect(frame.getByRole('region', { name: 'Denim Essentials' })).toBeVisible();
        // Premise: distinct desktop/mobile files — the fallback this test
        // proves after removal is only a meaningful signal because the two
        // differ.
        await expect.poll(() => mediaSrc(frame)).toMatch(/^\/media\/fixtures\/desktop\//);

        await openSettingsDrawer(page);

        const row = desktopImageTileRow(page);
        const tile = row.locator('.vanssa-media-tile');
        // Hidden unmapped checkbox (wrapped in `d-none`): nothing in ARIA
        // exposes "this slot is flagged for removal" beyond the tile's own
        // buttons and the "Removed on save" text asserted below, so this is
        // the one plugin-internal hook this test needs.
        const checkbox = row.locator('[data-vanssa-image-upload-preview-target="remove"]');

        await expect(checkbox).not.toBeChecked();
        await expect(tile).toMatchAriaSnapshot(`
            - button "Remove this media"
        `);
        await expect(row.getByText('Removed on save')).toBeHidden();
        await expectNoA11yViolations(page, '#vanssa-slide-media-settings');

        await row.getByRole('button', { name: 'Remove this media' }).click();

        // Flagged: the checkbox SlidePreviewController::applyMediaRemovals
        // reads to null the field out in memory (never flushed).
        await expect(checkbox).toBeChecked();
        await expect(tile).toMatchAriaSnapshot(`
            - button "Undo removal"
        `);
        await expect(row.getByText('Removed on save')).toBeVisible();

        // The flagged state is scanned too, not just the resting one:
        // "Removed on save" is the only text this markup adds after a click,
        // and Tabler's `.text-danger` fell under 4.5:1 on the card background
        // (media_tile.scss now uses `--tblr-danger-text-emphasis` instead).
        await expectNoA11yViolations(page, '#vanssa-slide-media-settings');

        // Live-updates: desktop's own cover is gone, so the SAME breakpoint's
        // preview now falls back to the mobile file
        // (Slide::getLocalizedMediaForBreakpoint) — an observable src change,
        // not just "the frame reloaded".
        await expect.poll(() => mediaSrc(frame), { timeout: 10_000 }).toMatch(/^\/media\/fixtures\/mobile\//);

        await row.getByRole('button', { name: 'Undo removal' }).click();

        await expect(checkbox).not.toBeChecked();
        await expect(tile).toMatchAriaSnapshot(`
            - button "Remove this media"
        `);
        await expect(row.getByText('Removed on save')).toBeHidden();
        await expectNoA11yViolations(page, '#vanssa-slide-media-settings');

        await expect.poll(() => mediaSrc(frame), { timeout: 10_000 }).toMatch(/^\/media\/fixtures\/desktop\//);

        // Read-only test: nothing was ever submitted. SlidePreviewController
        // applies the draft in memory only, so a hard reload must show the
        // persisted state completely untouched — no restore step needed.
        await page.reload();
        await expect(frame.getByRole('region', { name: 'Denim Essentials' })).toBeVisible();
        await expect.poll(() => mediaSrc(frame)).toMatch(/^\/media\/fixtures\/desktop\//);
        await expect(page.locator('#slide_slideCoverRemove')).not.toBeChecked();
    });
});
