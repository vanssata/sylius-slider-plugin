import { expect, type Locator, type Page } from '@playwright/test';

/**
 * `.vanssa-slide[data-slide-code]` is the plugin's own stable hook and the
 * selector the shop template scopes its per-slide `<style>` rules on. There is
 * no ARIA equivalent: every slide exposes the same `article` role with no
 * accessible name of its own.
 */
export const slideSelector = (code: string): string => `.vanssa-slide[data-slide-code="${code}"]`;

export function slide(scope: Page | Locator, code: string): Locator {
    return scope.locator(slideSelector(code));
}

/**
 * Assert that `code` is the slide currently exposed to assistive technology.
 *
 * This is a **premise guard**, not a feature assertion. The shop template marks
 * slide index 0 `is-active` and `slider_controller` sets `aria-hidden="true"`
 * on every other slide; Playwright's ARIA snapshots and axe both skip
 * aria-hidden subtrees. So any snapshot or a11y scan rooted on a named slide
 * silently degrades to "0 nodes, 0 violations" once that slide is no longer
 * first — which is exactly what happens if a reorder spec's restore step failed
 * in an earlier run.
 *
 * Failing here with a pointed message beats a mystifying empty-tree diff three
 * specs away from the cause.
 */
export async function expectSlideIsActive(scope: Page | Locator, code: string): Promise<void> {
    await expect(
        slide(scope, code),
        `slide "${code}" must be the active (first) slide for this assertion to see anything — ` +
            'if a reorder spec left the fixture order rotated, restore it with `make load-slider-fixtures`',
    ).toHaveClass(/\bis-active\b/);

    await expect(slide(scope, code)).not.toHaveAttribute('aria-hidden', 'true');
}
