import { expect, test } from '@playwright/test';

import { SLIDERS, VIEWPORTS, routes } from '../support/data';
import { freezeMotion, hold, recordGif, responsiveViewportFrame, setStageWidth, shot } from './support/media';

/**
 * Storefront documentation media generators.
 *
 * NOT tests — run with `make docs-media`. See docs/dev/docs-media.md.
 * Prerequisites: the stack up with APP_ENV=prod (no debug toolbar) and the
 * demo fixtures loaded (`make load-slider-fixtures`).
 */

test.describe('docs media: storefront', () => {
    test('slider page, desktop and mobile', async ({ page }) => {
        await page.setViewportSize(VIEWPORTS.desktop);
        await page.goto(routes.shopSlider(SLIDERS.classicArrows));
        await freezeMotion(page);

        const slider = page.getByRole('region', { name: 'Fashion Classic Arrows' });
        await expect(slider).toBeVisible();
        await shot(slider, 'frontend-slider-homepage-main');

        await page.setViewportSize(VIEWPORTS.mobile);
        await page.goto(routes.shopSlider(SLIDERS.classicArrows));
        await freezeMotion(page);
        await expect(slider).toBeVisible();
        await shot(slider, 'frontend-slider-homepage-main-mobile');
    });

    test('storefront-slider.gif — arrows advance the slides', async ({ page }) => {
        await page.setViewportSize(VIEWPORTS.desktop);
        await page.goto(routes.shopSlider(SLIDERS.classicArrows));

        const slider = page.getByRole('region', { name: 'Fashion Classic Arrows' });
        await expect(slider).toBeVisible();
        const next = page.getByRole('button', { name: 'Next slide' });

        const box = (await slider.boundingBox())!;
        const clip = { x: box.x, y: box.y, width: box.width, height: Math.min(box.height, 620) };

        const advance = async () => {
            await next.click();
            // Settle on the new active slide instead of sleeping a fixed time.
            await expect(slider.locator('.vanssa-slide.is-active')).toHaveCount(1);
        };

        await recordGif(
            page,
            'storefront-slider',
            [...hold(2), advance, ...hold(3), advance, ...hold(3), advance, ...hold(4)],
            { fps: 6, clip },
        );
    });

    test('responsive-breakpoints.gif — one slide across desktop, tablet and mobile', async ({ page }) => {
        // Visualises commit 4881b61: `new-collection` carries per-breakpoint
        // layout overrides (centred content on tablet, vertically centred on
        // mobile). Before that fix the breakpoint <style> block never rendered
        // and all three widths looked identical.
        //
        // The device width is simulated by resizing an IFRAME inside a fixed
        // 1400x900 viewport, not by page.setViewportSize(): frames of differing
        // sizes are silently dropped when ffmpeg assembles the GIF.
        await page.setViewportSize({ width: 1400, height: 900 });
        await responsiveViewportFrame(page, routes.shopSlider(SLIDERS.classicArrows), 820);

        await recordGif(
            page,
            'responsive-breakpoints',
            [
                ...hold(4),
                setStageWidth(page, VIEWPORTS.tablet.width),
                ...hold(4),
                setStageWidth(page, VIEWPORTS.mobile.width),
                ...hold(5),
                setStageWidth(page, VIEWPORTS.desktop.width),
                ...hold(2),
            ],
            { fps: 4 },
        );
    });
});
