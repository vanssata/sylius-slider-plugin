import AxeBuilder from '@axe-core/playwright';
import { expect, type Page } from '@playwright/test';

/**
 * ARIA snapshots verify that the structure you expect is present and stable.
 * They do NOT check colour contrast, invalid ARIA or duplicate ids — pair them
 * with axe in the same test (references/aria-testing.md).
 *
 * `include` scopes the scan to the plugin's own markup: the surrounding Sylius
 * admin/shop chrome has pre-existing violations this plugin cannot fix, and a
 * page-wide scan would fail on them and teach everyone to ignore the check.
 */
export async function expectNoA11yViolations(
    page: Page,
    include: string,
    { disableRules = [] as string[] } = {},
): Promise<void> {
    const results = await new AxeBuilder({ page })
        .include(include)
        .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'])
        .disableRules(disableRules)
        .analyze();

    expect(
        results.violations.map((v) => ({
            id: v.id,
            impact: v.impact,
            nodes: v.nodes.map((n) => n.target.join(' ')),
        })),
    ).toEqual([]);
}
