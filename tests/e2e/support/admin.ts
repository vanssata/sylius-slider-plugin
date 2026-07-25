import { expect, type Page } from '@playwright/test';

import { ADMIN } from './data';

/**
 * Log into the Sylius admin.
 *
 * Sylius' admin login form has no accessible <label> wiring on some themes, so
 * this helper targets the stable form field ids (`#_username` / `#_password`)
 * rather than roles — the ONLY place in this suite where that is acceptable.
 * Everything after login must use role/name locators.
 */
export async function loginAsAdmin(page: Page): Promise<void> {
    await page.goto('/admin/login');

    // Already authenticated (session reused): Sylius redirects away from /login.
    if (!page.url().includes('/admin/login')) {
        return;
    }

    await page.locator('#_username').fill(ADMIN.email);
    await page.locator('#_password').fill(ADMIN.password);
    await page.locator('form').filter({ has: page.locator('#_username') }).locator('[type="submit"]').click();

    await page.waitForURL((url) => !url.pathname.endsWith('/admin/login'), { timeout: 30_000 });
    await expect(page).not.toHaveURL(/\/admin\/login/);
}

/**
 * Resolve a slider's numeric admin id from its code by reading the grid.
 *
 * The demo fixtures are re-loadable, so ids are not stable across a
 * `make load-slider-fixtures` — never hard-code them in a spec.
 */
export async function sliderIdByCode(page: Page, code: string): Promise<string> {
    await page.goto('/admin/sliders/');
    const row = page.getByRole('row').filter({ hasText: code });
    await expect(row).toBeVisible();

    const href = await row.getByRole('link').first().getAttribute('href');
    const id = href?.match(/\/sliders\/(\d+)/)?.[1];
    if (!id) {
        throw new Error(`Could not resolve a slider id for code "${code}" from href "${href}"`);
    }

    return id;
}

/**
 * Same as {@link sliderIdByCode}, for slides.
 *
 * The slides grid has no edit *link*: the pencil row action is a button that
 * opens `#vanssa-slide-preview-modal-{id}` in place
 * (templates/admin/slide/grid/action/edit_modal.html.twig), so the id is read
 * off the modal target rather than an href.
 */
export async function slideIdByCode(page: Page, code: string): Promise<string> {
    await page.goto('/admin/slides/');
    const row = page.getByRole('row').filter({ hasText: code });
    await expect(row).toBeVisible();

    const target = await row
        .locator('[data-bs-target^="#vanssa-slide-preview-modal-"]')
        .first()
        .getAttribute('data-bs-target');
    const id = target?.match(/-(\d+)$/)?.[1];
    if (!id) {
        throw new Error(`Could not resolve a slide id for code "${code}" from modal target "${target}"`);
    }

    return id;
}
