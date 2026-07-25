import { expect, test } from '@playwright/test';

import { loginAsAdmin, openSettingsDrawer, openSettingsSection, slideIdByCode, sliderIdByCode } from '../support/admin';
import { SLIDERS, SLIDES, routes } from '../support/data';
import { freezeMotion, hold, recordGif, shot } from './support/media';

/**
 * Admin documentation media generators.
 *
 * NOT tests — run with `make docs-media`. See docs/dev/docs-media.md.
 * Prerequisites: stack up with APP_ENV=prod (no web debug toolbar) and the demo
 * fixtures loaded (`make load-slider-fixtures`).
 */

// Deliberately NOT serial: each generator logs in and navigates on its own, so
// one failing generator must not skip the remaining screenshots.
test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
});

/** The Sylius admin page body minus the sidebar — what a doc screenshot should show. */
const CONTENT = '.page-wrapper, main, .page-body';

test.describe('docs media: admin', () => {
    test('grid index pages', async ({ page }) => {
        await page.goto(routes.adminSliderIndex);
        await freezeMotion(page);
        await expect(page.getByRole('row').filter({ hasText: SLIDERS.classicArrows })).toBeVisible();
        await shot(page.locator(CONTENT).first(), 'admin-sliders-index');

        await page.goto(routes.adminSlideIndex);
        await freezeMotion(page);
        await expect(page.getByRole('row').filter({ hasText: SLIDES.newCollection })).toBeVisible();
        await shot(page.locator(CONTENT).first(), 'admin-slides-index');

        // The presets index is a card grid, not a <table> — wait for the
        // page-level "New" action instead of a grid element.
        await page.goto(routes.adminStylePresetIndex);
        await freezeMotion(page);
        await expect(page.locator(CONTENT).first()).toBeVisible();
        await page.waitForLoadState('networkidle');
        await shot(page.locator(CONTENT).first(), 'admin-style-presets-index');
    });

    test('slider editing workspace', async ({ page }) => {
        const id = await sliderIdByCode(page, SLIDERS.classicArrows);
        await page.goto(routes.adminSliderEdit(id));

        const workspace = page.locator('.vanssa-workspace');
        await expect(workspace).toBeVisible();
        await expect(page.locator(`turbo-frame#vanssa-slider-preview-frame-${id}`)).toBeVisible();
        await freezeMotion(page);

        // Preview toolbar: presets on the left, Settings + fullscreen on the right.
        await shot(page.locator('.vanssa-preview-panel').first(), 'admin-slider-preview-panel');

        // Full workspace with the settings drawer open — the documented default view.
        await openSettingsDrawer(page);
        await expect(page.locator('.vanssa-workspace')).toBeVisible();
        await page.waitForTimeout(400);
        await shot(page, 'admin-slider-edit-homepage-main');
    });

    test('add-slides browser modal', async ({ page }) => {
        const id = await sliderIdByCode(page, SLIDERS.classicArrows);
        await page.goto(routes.adminSliderEdit(id));
        await openSettingsDrawer(page);

        await openSettingsSection(page, 'Slides');

        const addButton = page.getByRole('button', { name: /^Add/ }).first();
        await expect(addButton).toBeVisible();
        await addButton.click();

        const modal = page.locator('.modal.show').first();
        await expect(modal).toBeVisible();
        await freezeMotion(page);
        await page.waitForTimeout(400);
        await shot(modal, 'admin-slider-add-slides-modal');
    });

    test('slide edit modal from the slides grid', async ({ page }) => {
        // Closes the docs/SCREENSHOTS.md gap: admin-slide-edit-modal.png never existed.
        await page.goto(routes.adminSlideIndex);
        const row = page.getByRole('row').filter({ hasText: SLIDES.newCollection });
        await row.locator('[data-bs-target^="#vanssa-slide-preview-modal-"]').first().click();

        const modal = page.locator('.modal.show').first();
        await expect(modal).toBeVisible();
        await freezeMotion(page);
        await page.waitForTimeout(600);
        await shot(modal, 'admin-slide-edit-modal');
    });

    test('slide edit page: preview and per-breakpoint media settings', async ({ page }) => {
        const id = await slideIdByCode(page, SLIDES.newCollection);
        await page.goto(routes.adminSlideEdit(id));

        await expect(page.locator('.vanssa-workspace')).toBeVisible();
        await freezeMotion(page);
        await shot(page.locator('.vanssa-preview-panel').first(), 'admin-slide-live-preview');

        await openSettingsDrawer(page);
        await expect(page.getByText('Media & Settings').first()).toBeVisible();
        await shot(page, 'admin-slide-media-settings');
    });

    test('slide edit page: per-locale translations', async ({ page }) => {
        // The Translations card is `.d-none` until the toolbar's Language
        // dropdown selects a locale — on edit pages the form is toolbar-driven
        // (form_context_controller), there is no per-locale accordion
        // (templates/admin/slide/form/sections/translations.html.twig:7-27).
        const id = await slideIdByCode(page, SLIDES.newCollection);
        await page.goto(routes.adminSlideEdit(id));
        await expect(page.locator('.vanssa-workspace')).toBeVisible();

        await openSettingsDrawer(page);
        await page.getByRole('combobox', { name: 'Language' }).selectOption('de_DE');

        const translations = page.locator('[data-vanssa-context-locale="de_DE"]').first();
        await expect(translations).toBeVisible();
        await freezeMotion(page);
        await shot(page, 'admin-slide-translations');
    });

    test('preset gallery on the create page', async ({ page }) => {
        await page.goto(routes.adminSliderNew);
        await freezeMotion(page);

        const gallery = page.locator('.modal.show, [data-controller~="vanssa-preset-gallery"]').first();
        await expect(gallery).toBeVisible();
        await page.waitForTimeout(400);
        await shot(gallery, 'admin-preset-gallery');
    });

    test('admin-workspace.gif — opening the settings drawer', async ({ page }) => {
        const id = await sliderIdByCode(page, SLIDERS.classicArrows);
        await page.goto(routes.adminSliderEdit(id));
        await expect(page.locator('.vanssa-workspace')).toBeVisible();
        await expect(page.locator(`turbo-frame#vanssa-slider-preview-frame-${id}`)).toBeVisible();

        await recordGif(
            page,
            'admin-workspace',
            [
                ...hold(3),
                async () => {
                    await openSettingsDrawer(page);
                    await page.waitForTimeout(500);
                },
                ...hold(4),
                async () => {
                    // The drawer head's own toggle, not the toolbar one.
                    await page.getByRole('button', { name: 'Close settings' }).first().click();
                    await page.waitForTimeout(500);
                },
                ...hold(2),
            ],
            { fps: 4 },
        );
    });

    test('preset-tryon.gif — hovering a preset previews it', async ({ page }) => {
        const id = await sliderIdByCode(page, SLIDERS.classicArrows);
        await page.goto(routes.adminSliderEdit(id));
        await expect(page.locator(`turbo-frame#vanssa-slider-preview-frame-${id}`)).toBeVisible();

        const presetToggle = page.getByRole('button', { name: 'Preset' }).first();
        await presetToggle.click();

        const hoverPreset = (name: string) => async () => {
            await page.getByRole('button', { name, exact: true }).first().hover();
            await page.waitForTimeout(700);
        };

        await recordGif(
            page,
            'preset-tryon',
            [
                ...hold(2),
                hoverPreset('Minimal Fade'),
                ...hold(3),
                hoverPreset('Fullscreen Hero'),
                ...hold(3),
                hoverPreset('Compact Banner'),
                ...hold(3),
            ],
            { fps: 4 },
        );
    });

    test('preset-gallery.gif — choosing a preset on the create page', async ({ page }) => {
        await page.goto(routes.adminSliderNew);

        const gallery = page.locator('.modal.show, [data-controller~="vanssa-preset-gallery"]').first();
        await expect(gallery).toBeVisible();

        const cards = gallery.getByRole('button');
        const count = Math.min(await cards.count(), 4);
        const steps = [];
        for (let i = 0; i < count; i += 1) {
            steps.push(async () => {
                await cards.nth(i).hover();
                await page.waitForTimeout(500);
            });
        }

        await recordGif(page, 'preset-gallery', [...hold(2), ...steps, ...hold(3)], { fps: 4 });
    });

    test('slide-edit-modal.gif — editing a slide without leaving the grid', async ({ page }) => {
        await page.goto(routes.adminSlideIndex);
        const row = page.getByRole('row').filter({ hasText: SLIDES.newCollection });
        const trigger = row.locator('[data-bs-target^="#vanssa-slide-preview-modal-"]').first();
        const modal = page.locator('.modal.show').first();

        await recordGif(
            page,
            'slide-edit-modal',
            [
                ...hold(2),
                async () => {
                    await trigger.click();
                    await expect(modal).toBeVisible();
                    await page.waitForTimeout(800);
                },
                ...hold(5),
                async () => {
                    await page.keyboard.press('Escape');
                    await page.waitForTimeout(600);
                },
                ...hold(2),
            ],
            { fps: 4 },
        );
    });

    test('add-slides-browser.gif — attaching slides from the browser modal', async ({ page }) => {
        const id = await sliderIdByCode(page, SLIDERS.classicArrows);
        await page.goto(routes.adminSliderEdit(id));
        await openSettingsDrawer(page);
        await openSettingsSection(page, 'Slides');

        const addButton = page.getByRole('button', { name: /^Add/ }).first();
        await expect(addButton).toBeVisible();
        const modal = page.locator('.modal.show').first();

        await recordGif(
            page,
            'add-slides-browser',
            [
                ...hold(2),
                async () => {
                    await addButton.click();
                    await expect(modal).toBeVisible();
                    await page.waitForTimeout(800);
                },
                ...hold(4),
                async () => {
                    await page.keyboard.press('Escape');
                    await page.waitForTimeout(600);
                },
                ...hold(2),
            ],
            { fps: 4 },
        );
    });
});
