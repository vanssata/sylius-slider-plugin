/**
 * Regression this catches: the per-breakpoint `<style>` block in
 * templates/components/vanssa_sylius_slider/shop/slide.html.twig was built with
 * `{% for settings, target in [[tabletResolved, 'tablet'], [mobileResolved, 'mobile']] %}`.
 * A two-variable Twig `for` over a plain array is key/value iteration (index +
 * inner array), NOT tuple destructuring — so `settings` was the integer 0/1,
 * every `settings.foo|default('')` collapsed to empty, `tabletVars`/`mobileVars`
 * stayed `[]` and the `@media (max-width: 1024px)` / `@media (max-width: 767px)`
 * rules were never emitted at all. Admins could save tablet/mobile overrides in
 * the slide form, see them honoured in the admin preview, and then get the plain
 * desktop layout on the real storefront at every viewport — silently, with no
 * error and no visible marker anywhere in the HTML. Fixed in 4881b61.
 *
 * The shape of that failure is very specific: with the breakpoint block missing,
 * every viewport resolves the DESKTOP values (flex-start / left / flex-end).
 * The tablet and mobile expectations below therefore fail closed on a revert —
 * see the per-breakpoint table for which value differs where.
 */

import { expect, test, type Page } from '@playwright/test';

import { expectNoA11yViolations } from '../support/a11y';
import { SLIDERS, SLIDES, routes } from '../support/data';
import { expectSlideIsActive, slideSelector } from '../support/storefront';

/** Breakpoint bands of the template's `@media` rules; keep in sync with it. */
const TABLET_MAX_WIDTH = 1024;
const MOBILE_MAX_WIDTH = 767;

type Breakpoint = 'desktop' | 'tablet' | 'mobile';

interface LayoutExpectation {
    /** Resolved per-slide CSS custom properties on the `<article>` root. */
    vars: { posX: string; posY: string; textAlign: string };
    /**
     * The visual consequence of those vars, so the spec keeps its meaning if the
     * custom properties are ever renamed: `--vanssa-slide-content-pos-x/-y` feed
     * `justify-content`/`align-items` on `.vanssa-slide__content`, and
     * `--vanssa-slide-text-align` feeds `text-align` on the content box.
     */
    layout: { justifyContent: string; alignItems: string; textAlign: string };
}

/**
 * `new-collection` is the one demo slide carrying per-breakpoint overrides
 * (src/Fixture/SliderDemoFixture.php): tablet re-centres the box horizontally,
 * mobile additionally re-centres it vertically. Everything else cascades down
 * from desktop.
 *
 *            pos-x       | pos-y      | text-align   <- differs from desktop?
 *  desktop   flex-start  | flex-end   | left
 *  tablet    center      | flex-end   | center       <- pos-x, text-align
 *  mobile    center      | center     | center       <- pos-x, pos-y, text-align
 */
const EXPECTED: Record<Breakpoint, LayoutExpectation> = {
    desktop: {
        vars: { posX: 'flex-start', posY: 'flex-end', textAlign: 'left' },
        layout: { justifyContent: 'flex-start', alignItems: 'flex-end', textAlign: 'left' },
    },
    tablet: {
        vars: { posX: 'center', posY: 'flex-end', textAlign: 'center' },
        layout: { justifyContent: 'center', alignItems: 'flex-end', textAlign: 'center' },
    },
    mobile: {
        vars: { posX: 'center', posY: 'center', textAlign: 'center' },
        layout: { justifyContent: 'center', alignItems: 'center', textAlign: 'center' },
    },
};

/** A slide with no overrides of its own — same layout at every breakpoint. */
const CONTROL_LAYOUT: LayoutExpectation = {
    vars: { posX: 'flex-start', posY: 'flex-end', textAlign: 'left' },
    layout: { justifyContent: 'flex-start', alignItems: 'flex-end', textAlign: 'left' },
};

/**
 * CSS, not a role locator, on purpose: all four slides expose the same
 * `article` role with no accessible name of their own, so nothing in the ARIA
 * tree identifies *which* slide this is. `[data-slide-code]` is the plugin's
 * own stable hook and is also the selector the template scopes its `<style>`
 * rules on, which is exactly what this spec is asserting about.
 *
 * The layout assertions below read computed style, which works on inactive
 * slides too — only the ARIA/axe test needs the slide to be the active one,
 * and it guards that premise explicitly.
 */

function breakpointFor(width: number): Breakpoint {
    if (width <= MOBILE_MAX_WIDTH) {
        return 'mobile';
    }

    return width <= TABLET_MAX_WIDTH ? 'tablet' : 'desktop';
}

/** Derived from the project's real viewport, so all three projects share one spec. */
function currentBreakpoint(page: Page): Breakpoint {
    const viewport = page.viewportSize();
    expect(viewport, 'every project in playwright.config.ts pins a viewport').not.toBeNull();

    return breakpointFor(viewport!.width);
}

function readVars(page: Page, code: string): Promise<LayoutExpectation['vars']> {
    return page.locator(slideSelector(code)).evaluate((slide) => {
        const computed = getComputedStyle(slide);

        return {
            posX: computed.getPropertyValue('--vanssa-slide-content-pos-x').trim(),
            posY: computed.getPropertyValue('--vanssa-slide-content-pos-y').trim(),
            textAlign: computed.getPropertyValue('--vanssa-slide-text-align').trim(),
        };
    });
}

function readLayout(page: Page, code: string): Promise<LayoutExpectation['layout']> {
    return page.locator(slideSelector(code)).evaluate((slide) => {
        const content = slide.querySelector('.vanssa-slide__content');
        const box = slide.querySelector('.vanssa-slide__content-box');
        if (null === content || null === box) {
            throw new Error('The slide rendered without a content box.');
        }

        const contentStyle = getComputedStyle(content);

        return {
            justifyContent: contentStyle.justifyContent,
            alignItems: contentStyle.alignItems,
            textAlign: getComputedStyle(box).textAlign,
        };
    });
}

test.describe('per-slide tablet/mobile overrides', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto(routes.shopSlider(SLIDERS.classicArrows));
        await expect(page.getByRole('region', { name: 'Fashion Classic Arrows' })).toBeVisible();
    });

    test('the browser agrees with the breakpoint this project is meant to exercise', async ({ page }) => {
        // Guards the premise of every other test here: if playwright.config.ts
        // viewports or the template's media bands ever drift apart, the
        // expectations below would quietly assert the wrong breakpoint.
        const expected = currentBreakpoint(page);

        const actual = await page.evaluate(
            ([mobileMax, tabletMax]) => {
                if (window.matchMedia(`(max-width: ${mobileMax}px)`).matches) {
                    return 'mobile';
                }

                return window.matchMedia(`(max-width: ${tabletMax}px)`).matches ? 'tablet' : 'desktop';
            },
            [MOBILE_MAX_WIDTH, TABLET_MAX_WIDTH],
        );

        expect(actual).toBe(expected);
    });

    test('new-collection resolves the layout variables of the current breakpoint', async ({ page }) => {
        const breakpoint = currentBreakpoint(page);

        // Polled, not read once: the per-slide `<style>` block is inline in the
        // document but the values are only meaningful once the shop stylesheet
        // that consumes them has been applied.
        await expect
            .poll(() => readVars(page, SLIDES.newCollection), {
                message: `resolved custom properties at the ${breakpoint} breakpoint`,
            })
            .toEqual(EXPECTED[breakpoint].vars);
    });

    test('new-collection lays its content box out the way those variables say', async ({ page }) => {
        const breakpoint = currentBreakpoint(page);

        await expect
            .poll(() => readLayout(page, SLIDES.newCollection), {
                message: `computed content layout at the ${breakpoint} breakpoint`,
            })
            .toEqual(EXPECTED[breakpoint].layout);
    });

    test('a slide without overrides is unaffected by its neighbour breakpoint rules', async ({ page }) => {
        // The `@media` rules are scoped per `data-slide-code`. summer-dresses
        // sits in the same slider as new-collection and must keep the desktop
        // layout at every breakpoint — otherwise the fix would have traded a
        // dead feature for one that leaks across slides.
        await expect
            .poll(() => readVars(page, SLIDES.summerDresses))
            .toEqual(CONTROL_LAYOUT.vars);
        await expect
            .poll(() => readLayout(page, SLIDES.summerDresses))
            .toEqual(CONTROL_LAYOUT.layout);
    });

    test('re-laying the slide out per breakpoint does not disturb its accessible structure', async ({ page }) => {
        // Premise: inactive slides are aria-hidden, and both the snapshot and
        // the axe scan below skip aria-hidden subtrees — they would report
        // "matches" and "0 violations" over 0 nodes if new-collection were no
        // longer the first slide. Fail here, loudly, instead.
        await expectSlideIsActive(page, SLIDES.newCollection);

        // Exactly one of the three `.vanssa-breakpoint-text--*` spans and one of
        // the breakpoint media variants may reach the a11y tree per viewport;
        // a broken `display` cascade would duplicate the heading text instead.
        // Inline rather than an external baseline: the tree is four lines and
        // must read identically for all three projects, which is the point.
        await expect(page.locator(slideSelector(SLIDES.newCollection))).toMatchAriaSnapshot(`
            - article:
              - img "New Collection"
              - heading "New Collection" [level=3]
              - text: Fresh looks for the season — dresses, denim and everyday essentials.
        `);

        // Scoped to the plugin's own slide markup: the surrounding Sylius shop
        // chrome has pre-existing violations this plugin cannot fix.
        await expectNoA11yViolations(page, slideSelector(SLIDES.newCollection));
    });
});
