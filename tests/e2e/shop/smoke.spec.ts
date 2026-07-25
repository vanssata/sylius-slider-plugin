import { expect, test } from '@playwright/test';

import { SLIDERS, routes } from '../support/data';

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
