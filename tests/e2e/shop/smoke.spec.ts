import { expect, test } from '@playwright/test';

import { CLASSIC_ARROWS_SLIDE_ORDER, SLIDERS, routes } from '../support/data';

/**
 * Regression this catches: the storefront slider route or channel resolution
 * breaking outright — every other shop spec is meaningless if this fails.
 * (Channel hostname is "localhost"; from inside the compose network the app is
 * only reachable as http://nginx, which resolves to no channel and 404s. The
 * playwright service therefore runs with network_mode: host.)
 */
test('storefront slider page renders the slider region', async ({ page }) => {
    await page.goto(routes.shopSlider(SLIDERS.classicArrows));

    await expect(page.getByRole('region', { name: 'Fashion Classic Arrows' })).toBeVisible();
    await expect(page.locator('[data-controller~="vanssa-slider"]')).toHaveCount(1);
});

/**
 * Slide order is a saved slider setting, and it is also the premise several
 * other shop and admin specs depend on: only the first slide is `is-active`,
 * the rest are `aria-hidden`, and ARIA snapshots and axe both skip
 * aria-hidden subtrees. A spec that reordered slides and failed to restore
 * them would otherwise show up as an empty snapshot somewhere else entirely.
 */
test('slides render in the order the fixture assigned', async ({ page }) => {
    await page.goto(routes.shopSlider(SLIDERS.classicArrows));

    const slides = page.locator('.vanssa-slide[data-slide-code]');
    await expect(slides).toHaveCount(CLASSIC_ARROWS_SLIDE_ORDER.length);

    expect(
        await slides.evaluateAll((elements) => elements.map((el) => el.getAttribute('data-slide-code'))),
    ).toEqual([...CLASSIC_ARROWS_SLIDE_ORDER]);

    // …and the first one is the active slide the other specs assume.
    await expect(slides.first()).toHaveClass(/\bis-active\b/);
});
