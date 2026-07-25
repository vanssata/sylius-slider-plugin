/**
 * Regression this catches: the storefront `vanssa-slider` Stimulus controller
 * silently never connecting on a page that still *looks* perfect — the exact
 * failure the `file:` dependency copy trap produces (CLAUDE.md: yarn classic
 * COPIES `@vanssa/sylius-slider-plugin` into node_modules instead of
 * symlinking it, and a stale copy or a dropped bridge-manifest key ships
 * server-rendered slider markup with no JS behind it). Slide 1 is painted,
 * the arrows and bullets are all there with correct accessible names, and only
 * *behaviour* is gone: arrows do nothing, bullets do nothing, the arrow keys do
 * nothing, autoplay never starts, the aria-live region stays empty and the
 * video slide never receives its `vanssa-slide-video` controller. Every
 * assertion below is therefore behavioural.
 *
 * Deliberately order-independent: the expected slide sequence is read out of
 * the rendered DOM, never hard-coded, so an admin drag-reorder spec running
 * against the same shared fixture data cannot make this spec lie.
 */

import { expect, test, type Page } from '@playwright/test';

import { expectNoA11yViolations } from '../support/a11y';
import { SLIDERS, SLIDES, routes } from '../support/data';

/**
 * Plugin-internal hooks. Everything the visitor can operate (arrows, bullets,
 * the region itself) is reached by role + accessible name; CSS is used only
 * where the plugin exposes no ARIA equivalent:
 *
 * - `[data-controller~="vanssa-slider"]` — the slider root. It is the same
 *   element as the `role="region"` wrapper, but scoping by controller name is
 *   what makes "the controller is mounted HERE" the subject of the test;
 * - `.vanssa-slide[data-slide-code]` — slide identity used by the fixtures and
 *   by the per-slide <style> blocks; slides expose no role of their own;
 * - `is-active` — the controller's own state class. The plugin does not mirror
 *   it into ARIA on the bullets (no `aria-current`), so there is nothing else
 *   to assert against; see the a11y notes reported alongside this spec;
 * - `.vanssa-slider__controls` / `[data-vanssa-slider-target="..."]` — the
 *   template's structural hooks, which are also the Stimulus target contract.
 */
const SLIDER_ROOT = '[data-controller~="vanssa-slider"]';
const SLIDE = '.vanssa-slide';
const ACTIVE_SLIDE = '.vanssa-slide.is-active';
const LIVE_REGION = '[data-vanssa-slider-target="liveUpdate"]';
const CONTROLS = '.vanssa-slider__controls';
const PAGINATION = '[data-vanssa-slider-target="pagination"]';

/** Slide codes in the order the server rendered them, top to bottom. */
async function slideOrder(page: Page): Promise<string[]> {
    return page
        .locator(`${SLIDER_ROOT} ${SLIDE}`)
        .evaluateAll((slides) => slides.map((slide) => slide.getAttribute('data-slide-code') ?? ''));
}

/**
 * Hydration barrier.
 *
 * `applyCurrentSlide(0)` inside `connect()` is what first writes "Slide 1 of N"
 * into the visually-hidden aria-live span; the server renders that span empty.
 * Waiting for the text is therefore an exact, sleep-free signal that the bundle
 * loaded, Stimulus started and THIS controller connected — and it doubles as
 * the assertion that the live region is wired at all.
 */
async function waitForSliderReady(page: Page, totalSlides: number): Promise<void> {
    await expect(page.locator(LIVE_REGION)).toHaveText(`Slide 1 of ${totalSlides}`);
}

async function activeSlideCode(page: Page): Promise<string | null> {
    return page.locator(ACTIVE_SLIDE).getAttribute('data-slide-code');
}

/** The options object the controller itself reads (`optionsValue`). */
async function sliderOptions(page: Page): Promise<Record<string, any>> {
    const raw = await page.locator(SLIDER_ROOT).getAttribute('data-vanssa-slider-options-value');

    return JSON.parse(raw ?? '{}');
}

/**
 * Record every active-slide change the controller makes inside a bounded
 * window, with a MutationObserver rather than by sampling.
 *
 * This exists only for the *negative* autoplay case: proving a non-event needs
 * an observation window, and there is no web-first assertion for "stays put".
 * The window is not an arbitrary sleep — it is derived from the slider's own
 * configured interval, so it is by construction longer than the single advance
 * we are asserting must NOT happen. Returning the observed sequence rather than
 * a boolean makes a failure self-explanatory.
 */
async function recordSlideChanges(page: Page, windowMs: number): Promise<string[]> {
    return page.evaluate(
        async ({ ms, rootSelector, activeSelector }) => {
            const root = document.querySelector(rootSelector);
            if (!root) {
                return ['<no-slider>'];
            }

            const read = () => root.querySelector(activeSelector)?.getAttribute('data-slide-code') ?? '';
            const seen: string[] = [read()];
            const observer = new MutationObserver(() => {
                const code = read();
                if (code !== '' && code !== seen[seen.length - 1]) {
                    seen.push(code);
                }
            });
            observer.observe(root, { subtree: true, attributes: true, attributeFilter: ['class'] });

            await new Promise((resolve) => window.setTimeout(resolve, ms));
            observer.disconnect();

            return seen;
        },
        { ms: windowMs, rootSelector: SLIDER_ROOT, activeSelector: ACTIVE_SLIDE },
    );
}

test.describe('storefront slider behaviour', () => {
    test('arrows and pagination move the active slide, and pagination reflects it', async ({ page }) => {
        // fashion-classic-arrows: autoplay OFF, rewind ON, arrows + dot
        // pagination, 4 slides — nothing moves unless the visitor asks.
        await page.goto(routes.shopSlider(SLIDERS.classicArrows));

        const order = await slideOrder(page);
        // Membership (not sequence) pins the fixture contract.
        expect([...order].sort()).toEqual(
            [SLIDES.newCollection, SLIDES.summerDresses, SLIDES.denimEssentials, SLIDES.graphicTees].sort(),
        );
        await waitForSliderReady(page, order.length);

        const previous = page.getByRole('button', { name: 'Previous slide' });
        const next = page.getByRole('button', { name: 'Next slide' });
        const bullet = (n: number) => page.getByRole('button', { name: `Go to slide ${n}` });

        expect(await activeSlideCode(page)).toBe(order[0]);
        await expect(bullet(1)).toHaveClass(/is-active/);

        await next.click();
        expect(await activeSlideCode(page)).toBe(order[1]);
        await expect(page.locator(LIVE_REGION)).toHaveText(`Slide 2 of ${order.length}`);
        await expect(bullet(2)).toHaveClass(/is-active/);
        await expect(bullet(1)).not.toHaveClass(/is-active/);

        // One click is exactly one step — a controller registered twice (two
        // Stimulus applications, see CLAUDE.md) would jump two.
        await next.click();
        expect(await activeSlideCode(page)).toBe(order[2]);

        await previous.click();
        expect(await activeSlideCode(page)).toBe(order[1]);
        await expect(bullet(2)).toHaveClass(/is-active/);

        // Pagination jumps straight to a slide instead of stepping.
        await bullet(order.length).click();
        expect(await activeSlideCode(page)).toBe(order[order.length - 1]);
        await expect(page.locator(LIVE_REGION)).toHaveText(`Slide ${order.length} of ${order.length}`);
        await expect(bullet(order.length)).toHaveClass(/is-active/);

        // rewind: true — next past the last slide wraps round to the first...
        expect((await sliderOptions(page)).rewind).toBe(true);
        await next.click();
        expect(await activeSlideCode(page)).toBe(order[0]);
        await expect(bullet(1)).toHaveClass(/is-active/);

        // ...and previous from the first wraps to the last.
        await previous.click();
        expect(await activeSlideCode(page)).toBe(order[order.length - 1]);

        // Exactly one slide is ever active, and the rest are hidden from
        // assistive tech.
        await expect(page.locator(ACTIVE_SLIDE)).toHaveCount(1);
        await expect(page.locator(ACTIVE_SLIDE)).toHaveAttribute('aria-hidden', 'false');
        await expect(page.locator(`${SLIDER_ROOT} ${SLIDE}[aria-hidden="true"]`)).toHaveCount(order.length - 1);
    });

    test('controls keep their accessible names and the region is keyboard operable', async ({ page }) => {
        await page.goto(routes.shopSlider(SLIDERS.classicArrows));
        const order = await slideOrder(page);
        await waitForSliderReady(page, order.length);

        // The controller REBUILDS the pagination on connect (`setupPagination()`
        // wipes innerHTML and re-creates every bullet from scratch). These
        // snapshots are the guard that the client-built controls carry the same
        // accessible names as the server-rendered ones they replaced — a
        // silently unlabelled bullet is invisible to a purely visual check.
        await expect(page.locator(CONTROLS)).toMatchAriaSnapshot(`
            - button "Previous slide"
            - button "Next slide"
        `);
        await expect(page.locator(PAGINATION)).toMatchAriaSnapshot(`
            - button "Go to slide 1"
            - button "Go to slide 2"
            - button "Go to slide 3"
            - button "Go to slide 4"
        `);

        // keyboardNavigation is on for this slider, so the region is a tab stop
        // and owns ArrowLeft/ArrowRight.
        expect((await sliderOptions(page)).keyboardNavigation).toBe(true);
        const region = page.getByRole('region', { name: 'Fashion Classic Arrows' });
        await expect(region).toHaveAttribute('tabindex', '0');
        await expect(region).toHaveAttribute('aria-roledescription', 'carousel');

        await region.press('ArrowRight');
        expect(await activeSlideCode(page)).toBe(order[1]);
        await expect(page.locator(LIVE_REGION)).toHaveText(`Slide 2 of ${order.length}`);

        await region.press('ArrowRight');
        expect(await activeSlideCode(page)).toBe(order[2]);

        await region.press('ArrowLeft');
        expect(await activeSlideCode(page)).toBe(order[1]);
        await expect(page.locator(LIVE_REGION)).toHaveText(`Slide 2 of ${order.length}`);

        // The arrow buttons are keyboard-reachable and Enter does what a click
        // does — they are real <button>s, not click-handled divs.
        await page.getByRole('button', { name: 'Next slide' }).press('Enter');
        expect(await activeSlideCode(page)).toBe(order[2]);
        await page.getByRole('button', { name: 'Go to slide 1' }).press('Enter');
        expect(await activeSlideCode(page)).toBe(order[0]);
    });

    test('autoplay advances the slider on its own', async ({ page }) => {
        // fashion-autoplay-showcase: autoplay enabled, 5s interval, 4 slides.
        // pauseOnHover is on, so this test must never move the mouse over the
        // slider — no clicks, no hovers anywhere below.
        await page.goto(routes.shopSlider(SLIDERS.autoplayShowcase));

        const order = await slideOrder(page);
        expect([...order].sort()).toEqual(
            [SLIDES.graphicTees, SLIDES.streetCaps, SLIDES.denimEssentials, SLIDES.seasonSale].sort(),
        );
        await waitForSliderReady(page, order.length);

        const options = await sliderOptions(page);
        expect(options.autoplay.enabled).toBe(true);
        const interval: number = options.autoplay.interval;
        expect(interval).toBe(5000);

        // Autoplay only runs while the slider intersects the viewport
        // (IntersectionObserver, threshold 0.2) — scroll it in without hovering.
        await page.locator(SLIDER_ROOT).scrollIntoViewIfNeeded();
        await expect(page.locator(SLIDER_ROOT)).toHaveClass(/is-in-view/);

        expect(await activeSlideCode(page)).toBe(order[0]);

        // Bounded poll instead of a sleep: the first hands-free advance lands
        // within one interval and the new slide then stays put for another
        // full interval, so a 250ms poll cannot miss it.
        await expect
            .poll(() => activeSlideCode(page), { timeout: interval * 2 + 2_000, intervals: [250] })
            .toBe(order[1]);
        await expect(page.locator(LIVE_REGION)).toHaveText(`Slide 2 of ${order.length}`);

        // It keeps going — a single advance could be a stray re-render.
        await expect
            .poll(() => activeSlideCode(page), { timeout: interval * 2 + 2_000, intervals: [250] })
            .toBe(order[2]);
        await expect(page.locator(LIVE_REGION)).toHaveText(`Slide 3 of ${order.length}`);
    });

    test('a slider with autoplay disabled never advances by itself', async ({ page }) => {
        await page.goto(routes.shopSlider(SLIDERS.classicArrows));
        const order = await slideOrder(page);
        await waitForSliderReady(page, order.length);

        // Same viewport precondition as the positive case, so the two tests
        // differ in exactly one thing: the autoplay option.
        await page.locator(SLIDER_ROOT).scrollIntoViewIfNeeded();
        await expect(page.locator(SLIDER_ROOT)).toHaveClass(/is-in-view/);

        // The controller reads autoplay.enabled out of this value; assert the
        // contract it is handed before asserting what it does with it.
        const options = await sliderOptions(page);
        expect(options.autoplay.enabled).toBe(false);

        // Observation window sized from the interval the controller WOULD have
        // used had it wrongly started (5s), plus margin.
        const changes = await recordSlideChanges(page, options.autoplay.interval + 1_500);
        expect(changes).toEqual([order[0]]);
    });

    test('a video slide renders its media element with the slide-video controller attached', async ({ page }) => {
        // fashion-fullscreen-hero carries the runway-video slide. This slider
        // autoplays, so nothing here may depend on which slide is active.
        await page.goto(routes.shopSlider(SLIDERS.fullscreenHero));
        const order = await slideOrder(page);
        expect(order).toContain(SLIDES.runwayVideo);
        await waitForSliderReady(page, order.length);

        const videoSlide = page.locator(`${SLIDE}[data-slide-code="${SLIDES.runwayVideo}"]`);
        await expect(videoSlide).toHaveCount(1);

        const video = videoSlide.locator('video.vanssa-slide__media');
        await expect(video).toHaveCount(1);
        await expect(video.locator('source')).toHaveAttribute('src', /\.mp4$/);
        // Markup contract the stimulus-bridge manifest has to satisfy.
        await expect(video).toHaveAttribute('data-controller', /(^|\s)vanssa-slide-video(\s|$)/);
        await expect(video).toHaveAttribute('data-vanssa-slide-video-playback-value', 'autoplay');
        // The slider autoplays, so `loop` must be absent: the video has to be
        // able to fire `ended` for the video-gated advance to work at all.
        expect(await video.getAttribute('loop')).toBeNull();

        // Proof that the controller is really CONNECTED, without depending on
        // playback (headless autoplay policy makes real playback unreliable):
        // `setupVideoEvents()` translates the media element's native `playing`
        // event into a bubbling `vanssa-slide-video:playing`. Dispatching a
        // synthetic native event and observing the translated one is fully
        // synchronous — if the controller never connected, nothing answers.
        await expect
            .poll(
                () =>
                    page.evaluate((selector) => {
                        const media = document.querySelector(selector);
                        if (!media) {
                            return 'no-media';
                        }

                        let translated = false;
                        const listener = () => {
                            translated = true;
                        };
                        media.addEventListener('vanssa-slide-video:playing', listener);
                        media.dispatchEvent(new Event('playing'));
                        media.removeEventListener('vanssa-slide-video:playing', listener);

                        return translated ? 'controller-attached' : 'controller-missing';
                    }, `${SLIDE}[data-slide-code="${SLIDES.runwayVideo}"] video.vanssa-slide__media`),
                { timeout: 10_000, intervals: [200] },
            )
            .toBe('controller-attached');
    });

    test('the slider region has no accessibility violations', async ({ page }) => {
        await page.goto(routes.shopSlider(SLIDERS.classicArrows));
        const order = await slideOrder(page);
        await waitForSliderReady(page, order.length);

        // Scoped to the plugin's own markup on purpose — the surrounding Sylius
        // shop chrome carries pre-existing violations this plugin cannot fix,
        // and a page-wide scan would teach everyone to ignore this check.
        await expectNoA11yViolations(page, SLIDER_ROOT);

        // ...and again after a transition, because the aria-hidden bookkeeping
        // and the whole pagination list are rewritten as the slider moves.
        await page.getByRole('button', { name: 'Next slide' }).click();
        expect(await activeSlideCode(page)).toBe(order[1]);
        await expectNoA11yViolations(page, SLIDER_ROOT);
    });
});
