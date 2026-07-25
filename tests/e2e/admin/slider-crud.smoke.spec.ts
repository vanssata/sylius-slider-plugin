import { expect, test, type Page } from '@playwright/test';

import { expectNoA11yViolations } from '../support/a11y';
import { loginAsAdmin, openSettingsDrawer, sliderIdByCode } from '../support/admin';
import { SLIDERS, routes } from '../support/data';

/**
 * Regression this catches: the slider admin silently degrading to Sylius' own
 * fallback CRUD. The plugin owns `/admin/sliders/*` only through Twig hooks
 * (config/twig_hooks/admin/slider.yaml) — rename or drop
 * `sylius_admin.slider.update.content.form` and the edit page STILL answers
 * 200, still renders every form field, and still saves; it just loses the live
 * preview surface and the settings drawer that the whole workspace is built
 * around. Same for `sylius_admin.slider.create.content.preset_gallery` on the
 * create page. Field-level tests (Behat) keep passing through that breakage;
 * only asserting that the workspace and the preset dialog actually render
 * catches it. The create -> delete round trip additionally catches the
 * resource route losing its `create`/`delete` operations or the grid losing
 * its row actions, which a read-only spec would never notice.
 */

/** Unique to this spec so concurrent specs cannot collide on it. */
const NEW_SLIDER_CODE = 'e2e-smoke-slider';

/**
 * Delete a slider from the grid, if a row with that code exists.
 *
 * Sylius renders the delete row action through its shared `delete_modal`
 * component (vendor, not this plugin): an icon-only <button> carrying no
 * accessible name at all — the label lives in a `data-bs-title` tooltip on a
 * wrapper <div>, which never reaches the accessibility tree. Its
 * `data-bs-target` is therefore the only handle, and it doubles as the only
 * link between a row and its `#delete-modal-{id}` dialog, so both are matched
 * by CSS here. The confirmation itself is clicked by role.
 */
async function deleteSliderByCode(page: Page, code: string): Promise<boolean> {
    await page.goto(routes.adminSliderIndex);

    const row = page.getByRole('row').filter({ hasText: code });
    if ((await row.count()) === 0) {
        return false;
    }

    const trigger = row.first().locator('[data-bs-target^="#delete-modal-"]');
    const modalSelector = await trigger.getAttribute('data-bs-target');
    if (modalSelector === null) {
        throw new Error(`Row for slider "${code}" has no delete action`);
    }

    await trigger.click();

    const confirmation = page.locator(modalSelector);
    await expect(confirmation).toBeVisible();
    await confirmation.getByRole('button', { name: 'Delete', exact: true }).click();

    await expect(page.getByRole('row').filter({ hasText: code })).toHaveCount(0);

    return true;
}

test('slider grid lists every demo slider', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto(routes.adminSliderIndex);

    // The grid's default sort is `code: asc` (config/grids/admin/slider.yaml),
    // so row order is deterministic. ARIA snapshots match children in
    // "contain" mode, which is what keeps this stable while another spec has a
    // temporary slider of its own in the grid: extra rows are tolerated, the
    // six fixture rows must still be there, in order, with their slide counts.
    // (Under `code: asc` an `e2e-…` row sorts ABOVE every `fashion-…` one, so
    // the tolerated extra appears at the top, not the bottom.)
    await expect(page.getByRole('table')).toMatchAriaSnapshot({ name: 'sliders-grid.aria.yml' });
});

test('slider edit page renders the preview + settings workspace', async ({ page }) => {
    await loginAsAdmin(page);
    const id = await sliderIdByCode(page, SLIDERS.classicArrows);
    await page.goto(routes.adminSliderEdit(id));

    // Live preview surface: enough to prove the workspace half is present.
    // What the preview actually renders, its turbo-frame contract and its
    // context controls belong to preview.spec.ts — asserting them again here
    // would just duplicate that file.
    await expect(page.getByRole('region', { name: 'Fashion Classic Arrows' })).toBeVisible();

    // Settings drawer: collapsed until the toolbar toggle is used.
    const drawer = page.getByRole('complementary', { name: 'Slider settings' });
    await expect(drawer).toBeHidden();
    await openSettingsDrawer(page);
    await expect(drawer).toBeVisible();
    await expect(drawer.getByRole('button', { name: 'Save changes' })).toBeVisible();

    // The drawer body is one flat accordion — no nested cards, one item per
    // settings group (templates/admin/slider/form/sections.html.twig).
    const sections = drawer.locator('#vanssa-slider-sections');
    await expect(sections).toMatchAriaSnapshot(`
        - heading "General" [level=3]:
          - button "General"
        - textbox "Code*" [disabled]: fashion-classic-arrows
        - combobox "Enabled"
        - group "Channels"
        - textbox "Css classes"
        - heading "Layout & Spacing" [level=3]:
          - button "Layout & Spacing"
        - heading "Behavior & Effects" [level=3]:
          - button "Behavior & Effects"
        - heading "Arrows & Navigation" [level=3]:
          - button "Arrows & Navigation"
        - heading "Pagination" [level=3]:
          - button "Pagination"
        - heading "Autoplay" [level=3]:
          - button "Autoplay"
        - heading "Translations" [level=3]:
          - button "Translations"
        - heading "Slides" [level=3]:
          - button "Slides"
    `);

    // Scoped to the plugin's own settings form. Deliberately NOT the whole
    // `.vanssa-workspace`: the surrounding preview toolbar has a real
    // `aria-required-children` violation (a role="tablist" whose children are
    // plain buttons) that is reported as a finding rather than silenced here,
    // and it belongs to the preview surface, not to this CRUD spec.
    await expectNoA11yViolations(page, '#vanssa-slider-sections');
});

test('creates a slider through the UI and deletes it again', async ({ page }) => {
    await loginAsAdmin(page);

    // A row left behind by an interrupted earlier run would fail the unique
    // code check on Create; clearing it first is what makes re-runs idempotent.
    await deleteSliderByCode(page, NEW_SLIDER_CODE);

    try {
        await page.goto(routes.adminSliderNew);

        // The preset gallery auto-opens on the create page; "Blank" just
        // dismisses it and leaves an empty form.
        const presetGallery = page.getByRole('dialog').filter({ hasText: 'Start from a preset' });
        await expect(presetGallery).toBeVisible();
        await expect(presetGallery.getByRole('button', { name: 'Classic Arrows' })).toBeVisible();
        await presetGallery.getByRole('button', { name: 'Blank' }).click();
        await expect(presetGallery).toBeHidden();

        await page.getByRole('textbox', { name: 'Code' }).fill(NEW_SLIDER_CODE);
        await page.getByRole('button', { name: 'Create' }).click();

        // `redirect: update` (config/routes/admin.yaml) — a successful create
        // lands straight on the new slider's edit page, workspace and all.
        await page.waitForURL(/\/admin\/sliders\/\d+\/edit/);
        await expect(page.locator('.vanssa-workspace')).toBeVisible();
        await openSettingsDrawer(page);
        await expect(page.getByRole('textbox', { name: 'Code' })).toHaveValue(NEW_SLIDER_CODE);

        await page.goto(routes.adminSliderIndex);
        await expect(page.getByRole('row').filter({ hasText: NEW_SLIDER_CODE })).toHaveCount(1);
    } finally {
        // Runs even when an assertion above blew up, so the dataset is never
        // left dirty for the next run or for a concurrent spec.
        //
        // Swallowed on purpose — and NOT because "there is nothing to assert":
        // deleteSliderByCode contains expect() calls of its own, and an
        // exception thrown out of a finally block REPLACES the real failure
        // with a cleanup error. The assertion below keeps the teeth on the
        // happy path, where it is the only thing that can fail.
        await deleteSliderByCode(page, NEW_SLIDER_CODE).catch((error: unknown) => {
            console.warn(`cleanup: could not remove "${NEW_SLIDER_CODE}": ${String(error)}`);
        });
    }

    await expect(page.getByRole('row').filter({ hasText: NEW_SLIDER_CODE })).toHaveCount(0);
});
