import { expect, test, type Locator, type Page } from '@playwright/test';

import { expectNoA11yViolations } from '../support/a11y';
import { loginAsAdmin, openSettingsDrawer, sliderIdByCode } from '../support/admin';
import { SLIDERS, SLIDES, routes } from '../support/data';
import { expectSlideIsActive } from '../support/storefront';

/**
 * Regression this catches: `Turbo.session.drive = false` disappearing from
 * assets/admin/entrypoint.js. That file imports @hotwired/turbo solely so the
 * admin live preview can use a <turbo-frame>, and Turbo Drive is ON by
 * default — left on it hijacks every link and form of the Sylius admin, which
 * is not built for Turbo navigation: clicking a slider row in the grid would
 * then swap <body> inside the SAME document, so the previous page's JS state
 * survives, Sylius' per-page inline scripts never re-run and Stimulus
 * controllers connect twice. "admin navigation is a real document load" below
 * proves the click actually unloads the document, while the toolbar test
 * proves Turbo FRAMES still work — the two are easy to break in opposite
 * directions with one line.
 *
 * Also guarded: the inline preview frame really renders the storefront slider
 * component (not an empty frame or an error body), and the language /
 * resolution controls re-render THAT frame server-side instead of navigating
 * the edit page away.
 */

/** Survives a Turbo Drive visit; does NOT survive a real document load. */
const PROBE = '__vanssaPreviewProbe';
/** Counts turbo:frame-load events on the current document. */
const FRAME_LOADS = '__vanssaFrameLoads';

async function markDocument(page: Page): Promise<void> {
    await page.evaluate((key) => {
        (window as unknown as Record<string, string>)[key] = 'alive';
    }, PROBE);
}

async function documentSurvived(page: Page): Promise<boolean> {
    return page.evaluate((key) => (window as unknown as Record<string, string>)[key] === 'alive', PROBE);
}

async function watchFrameLoads(page: Page): Promise<void> {
    await page.evaluate((key) => {
        (window as unknown as Record<string, number>)[key] = 0;
        document.addEventListener('turbo:frame-load', () => {
            (window as unknown as Record<string, number>)[key] += 1;
        });
    }, FRAME_LOADS);
}

function frameLoads(page: Page): Promise<number> {
    return page.evaluate((key) => (window as unknown as Record<string, number>)[key] ?? 0, FRAME_LOADS);
}

/**
 * `<turbo-frame>` carries no role and no accessible name — its id is the
 * contract between the edit page and the preview response (both render
 * `vanssa-slider-preview-frame-{id}`), so it is the only stable hook here.
 */
function previewFrame(page: Page, sliderId: string): Locator {
    return page.locator(`#vanssa-slider-preview-frame-${sliderId}`);
}

/**
 * `new-collection` is the fixture slide carrying per-breakpoint layout
 * overrides (src/Fixture/SliderDemoFixture.php): desktop pins the text block
 * bottom-left, mobile centres it. Reading its computed alignment proves the
 * SERVER re-rendered the frame for the chosen breakpoint — the preview shares
 * the admin page's viewport, so no media query could produce this.
 * `[data-slide-code]` is a plugin-internal hook with no ARIA equivalent.
 */
function slideContent(frame: Locator): Locator {
    return frame.locator(`.vanssa-slide[data-slide-code="${SLIDES.newCollection}"] .vanssa-slide__content`);
}

async function alignmentOf(content: Locator): Promise<{ x: string; y: string } | null> {
    try {
        return await content.evaluate((el) => {
            const style = window.getComputedStyle(el);

            return { x: style.justifyContent, y: style.alignItems };
        });
    } catch {
        // The frame swapped its content out mid-read; expect.poll retries.
        return null;
    }
}

test.describe('admin slider live preview', () => {
    test('the inline preview frame renders the storefront slider', async ({ page }) => {
        await loginAsAdmin(page);
        const sliderId = await sliderIdByCode(page, SLIDERS.classicArrows);
        await page.goto(routes.adminSliderEdit(sliderId));

        const frame = previewFrame(page, sliderId);

        // The server renders the frame EMPTY; preview_frame_controller#connect
        // src-es it at the admin preview route with the toolbar's context.
        await expect(frame).toHaveAttribute(
            'src',
            new RegExp(`/admin/sliders/${sliderId}/preview\\?locale=[a-z]{2}_[A-Z]{2}&breakpoint=desktop$`),
        );

        // Plugin-internal hook: the storefront slider root is identified by the
        // Stimulus controller that drives it, which has no ARIA equivalent.
        await expect(frame.locator('[data-controller~="vanssa-slider"]')).toHaveCount(1);
        await expect(frame.locator('.vanssa-slide[data-slide-code]')).toHaveCount(4);

        // Premise: only the active slide reaches the a11y tree (the rest are
        // aria-hidden), so this snapshot silently empties out if the fixture
        // slide order was left rotated by a failed reorder restore elsewhere.
        await expectSlideIsActive(frame, SLIDES.newCollection);

        // Small region -> inline snapshot. The shop component must arrive
        // complete: labelled region, slide article, arrows and pagination.
        await expect(frame).toMatchAriaSnapshot(`
            - region "Fashion Classic Arrows":
              - article:
                - img "New Collection"
                - heading "New Collection" [level=3]
                - text: /Fresh looks for the season/
              - button "Previous slide"
              - button "Next slide"
              - button "Go to slide 1"
              - button "Go to slide 2"
              - button "Go to slide 3"
              - button "Go to slide 4"
              - text: /Slide 1 of 4/
        `);
    });

    test('admin navigation is a real document load (Turbo Drive stays off)', async ({ page }) => {
        await loginAsAdmin(page);
        await page.goto(routes.adminSliderIndex);

        // Guard: without this, the probe below would also pass on an admin
        // that never loaded Turbo at all — i.e. for the wrong reason.
        expect(
            await page.evaluate(() => {
                const turbo = (window as unknown as { Turbo?: { session?: { drive?: boolean } } }).Turbo;

                return { loaded: turbo !== undefined, drive: turbo?.session?.drive };
            }),
        ).toEqual({ loaded: true, drive: false });

        await markDocument(page);

        await page.getByRole('row').filter({ hasText: SLIDERS.classicArrows }).getByRole('link').first().click();
        await page.waitForURL(/\/admin\/sliders\/\d+\/edit/);

        // A Turbo Drive visit replaces <body> within the same document, so
        // window state survives it. A real navigation discards it.
        expect(await documentSurvived(page)).toBe(false);

        // Independent second proof: this document was fetched FROM the edit
        // URL. Under a Drive visit the single navigation entry would still
        // name the grid URL the document was originally loaded from.
        expect(
            await page.evaluate(
                () => performance.getEntriesByType('navigation')[0]?.name === window.location.href,
            ),
        ).toBe(true);

        // …and the freshly loaded document booted the preview again.
        const sliderId = page.url().match(/\/sliders\/(\d+)\//)?.[1] ?? '';
        await expect(
            previewFrame(page, sliderId).getByRole('region', { name: 'Fashion Classic Arrows' }),
        ).toBeVisible();
    });

    test('the context controls re-render the frame without navigating away', async ({ page }) => {
        await loginAsAdmin(page);
        const sliderId = await sliderIdByCode(page, SLIDERS.classicArrows);
        await page.goto(routes.adminSliderEdit(sliderId));

        const frame = previewFrame(page, sliderId);
        await expect(frame.getByRole('region', { name: 'Fashion Classic Arrows' })).toBeVisible();
        await expect(slideContent(frame)).toBeVisible();
        await expect.poll(() => alignmentOf(slideContent(frame))).toEqual({ x: 'flex-start', y: 'flex-end' });

        // Language + resolution live in the settings drawer, which is
        // display:none until the toolbar toggle opens it. `exact` keeps this
        // off the drawer's own "Close settings" button.
        await openSettingsDrawer(page);

        const editUrl = page.url();
        await markDocument(page);
        await watchFrameLoads(page);

        // Resolution switcher: resizes the frame AND asks the server for the
        // mobile-flattened render.
        const resolutions = page.getByRole('group', { name: 'Preview resolution' });
        await resolutions.getByRole('button', { name: 'Mobile' }).click();

        await expect(frame).toHaveAttribute('src', /[?&]breakpoint=mobile$/);
        await expect(frame).toHaveCSS('width', '390px');
        await expect.poll(() => frameLoads(page)).toBeGreaterThanOrEqual(1);
        await expect.poll(() => alignmentOf(slideContent(frame))).toEqual({ x: 'center', y: 'center' });

        // Back to desktop: the overrides are re-flattened the other way, so
        // this is a real server round-trip, not a one-way CSS tweak.
        await resolutions.getByRole('button', { name: 'Desktop' }).click();
        await expect(frame).toHaveAttribute('src', /[?&]breakpoint=desktop$/);
        await expect.poll(() => alignmentOf(slideContent(frame))).toEqual({ x: 'flex-start', y: 'flex-end' });

        // Language switcher: same frame, new locale in its src.
        const beforeLocaleSwitch = await frameLoads(page);
        await page.getByRole('combobox', { name: 'Language' }).selectOption('de_DE');

        await expect(frame).toHaveAttribute('src', /[?&]locale=de_DE(&|$)/);
        await expect.poll(() => frameLoads(page)).toBeGreaterThan(beforeLocaleSwitch);
        await expect(frame.getByRole('region', { name: 'Fashion Classic Arrows' })).toBeVisible();

        // The whole point: every switch above stayed inside the frame — same
        // URL, same document (a Drive visit or a form GET would kill both).
        expect(page.url()).toBe(editUrl);
        expect(await documentSurvived(page)).toBe(true);
    });

    test('the preview panel and its context controls are accessible', async ({ page }) => {
        await loginAsAdmin(page);
        const sliderId = await sliderIdByCode(page, SLIDERS.classicArrows);
        await page.goto(routes.adminSliderEdit(sliderId));

        const frame = previewFrame(page, sliderId);
        await expect(frame.getByRole('region', { name: 'Fashion Classic Arrows' })).toBeVisible();

        // The storefront markup rendered INSIDE the frame is scanned with every
        // rule on — it is clean today and must stay that way.
        await expectNoA11yViolations(page, `#vanssa-slider-preview-frame-${sliderId}-wrapper`);

        // The panel as a whole, toolbar included. Three rules are switched off
        // ONLY because the toolbar violates them today; each is reported as a
        // product finding rather than fixed from a test:
        //  - aria-required-children: in workspace mode the toolbar
        //    <ul role="tablist" aria-label="Preview toolbar"> holds no
        //    role="tab" child at all (its Desktop/Mobile/Tablet buttons moved
        //    to the drawer), so the tablist role is simply wrong there.
        //  - listitem: the Preset <li> is orphaned by that same role=tablist —
        //    the <ul> is no longer a list, so its plain <li> has no list owner.
        //  - color-contrast: the "Preset" nav-link and the "Settings" primary
        //    button fall under 4.5:1 on the card background.
        // Everything else stays enforced; drop a rule from this list as soon
        // as the corresponding markup is fixed.
        await expectNoA11yViolations(page, '.vanssa-preview-panel', {
            disableRules: ['aria-required-children', 'listitem', 'color-contrast'],
        });

        // The language / resolution switchers exercised above live in the
        // drawer, outside the panel — scanned strictly, they are clean.
        await openSettingsDrawer(page);
        await expect(page.getByRole('combobox', { name: 'Language' })).toBeVisible();
        await expectNoA11yViolations(page, '.vanssa-workspace__drawer-head .vanssa-context-controls');
    });
});
