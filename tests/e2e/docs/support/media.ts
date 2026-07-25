import { execFileSync } from 'node:child_process';
import { mkdirSync, readFileSync, readdirSync, rmSync } from 'node:fs';
import path from 'node:path';

import type { Locator, Page } from '@playwright/test';

/**
 * Screenshot and GIF helpers for the `docs-media` Playwright project.
 *
 * These are generators, not tests — `make e2e` excludes them, `make docs-media`
 * runs them. Output goes straight into the committed doc assets.
 */

const REPO_ROOT = path.resolve(__dirname, '../../../..');
export const SCREENSHOT_DIR = path.join(REPO_ROOT, 'docs/screenshots');
export const MEDIA_DIR = path.join(REPO_ROOT, 'docs/media');
const FRAME_DIR = path.join(REPO_ROOT, 'var/docs-media-frames');

/**
 * Freeze everything that would otherwise make two runs of the same generator
 * produce two different images: CSS animations/transitions, the slider's own
 * entrance animation, caret blink, and smooth scrolling.
 *
 * Deliberately NOT applied to the GIF generators — motion is the point there.
 */
export async function freezeMotion(page: Page): Promise<void> {
    await hideBrokenImages(page);
    await page.addStyleTag({
        content: `
            *, *::before, *::after {
                animation-duration: 0s !important;
                animation-delay: 0s !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0s !important;
                transition-delay: 0s !important;
                caret-color: transparent !important;
                scroll-behavior: auto !important;
            }
        `,
    });
}

/**
 * Hide images that failed to load, so a host-app asset gap does not put a
 * broken-image glyph in the plugin's documentation.
 *
 * Concretely: the Sylius admin brand image is served from
 * `/build/admin/images/sylius-logo-dark-text.png`, but the test application's
 * webpack config emits no `images/` directory at all — the file 404s in this
 * dev stack. That is an upstream `sylius/test-application` gap (and `vendor/`
 * is off limits), not something a reader of these docs needs to see.
 */
export async function hideBrokenImages(page: Page): Promise<void> {
    await page.evaluate(() => {
        document.querySelectorAll('img').forEach((img) => {
            if (img.complete && img.naturalWidth === 0) {
                img.style.visibility = 'hidden';
            }
        });
    });
}

/** Full-page or element screenshot into docs/screenshots/<name>.png. */
export async function shot(
    target: Page | Locator,
    name: string,
    { fullPage = false }: { fullPage?: boolean } = {},
): Promise<void> {
    mkdirSync(SCREENSHOT_DIR, { recursive: true });
    const file = path.join(SCREENSHOT_DIR, `${name}.png`);

    if ('screenshot' in target && 'goto' in target) {
        await (target as Page).screenshot({ path: file, fullPage, animations: 'disabled' });
        return;
    }

    await (target as Locator).screenshot({ path: file, animations: 'disabled' });
}

/**
 * Record a GIF from a series of explicit screenshot frames.
 *
 * Playwright can record `.webm`, but its frame timing is variable, so the same
 * scripted interaction produces a different file every run — useless for a
 * committed doc asset that should only change when the UI does. Driving the
 * frames ourselves and assembling them with ffmpeg is reproducible.
 *
 * `palettegen`/`paletteuse` gives a per-clip optimised 256-colour palette;
 * without it, GIF quantisation of a screenshot-heavy UI bands badly.
 */
export async function recordGif(
    page: Page,
    name: string,
    steps: Array<() => Promise<void>>,
    { fps = 8, clip }: { fps?: number; clip?: { x: number; y: number; width: number; height: number } } = {},
): Promise<void> {
    const frames = path.join(FRAME_DIR, name);
    rmSync(frames, { recursive: true, force: true });
    mkdirSync(frames, { recursive: true });
    mkdirSync(MEDIA_DIR, { recursive: true });

    // Applied here rather than at each call site: every frame of every GIF
    // wants it, and a host-app asset that 404s is never part of the story.
    await hideBrokenImages(page);

    let index = 0;
    const capture = async () => {
        await page.screenshot({
            path: path.join(frames, `frame-${String(index).padStart(4, '0')}.png`),
            animations: 'disabled',
            clip,
        });
        index += 1;
    };

    await capture();
    for (const step of steps) {
        await step();
        await capture();
    }

    assertUniformFrameSize(frames, name);

    execFileSync(
        'ffmpeg',
        [
            '-y',
            '-loglevel', 'error',
            '-framerate', String(fps),
            '-i', path.join(frames, 'frame-%04d.png'),
            '-filter_complex',
            '[0:v] split [a][b];[a] palettegen=stats_mode=diff [p];[b][p] paletteuse=dither=bayer:bayer_scale=3',
            '-loop', '0',
            path.join(MEDIA_DIR, `${name}.gif`),
        ],
        { stdio: 'inherit' },
    );

    rmSync(frames, { recursive: true, force: true });
}

/**
 * Hold on the current state for `times` extra frames — GIF playback needs a
 * pause on the "result" frame or the reader never sees it.
 */
export function hold(times: number): Array<() => Promise<void>> {
    return Array.from({ length: times }, () => async () => {});
}

/**
 * ffmpeg's image2 demuxer silently DROPS frames whose dimensions differ from
 * the first one — a generator that resizes the viewport mid-recording produces
 * a GIF with three frames and no error. Fail loudly instead.
 *
 * (Reads the PNG IHDR chunk directly: width/height are bytes 16..24.)
 */
function assertUniformFrameSize(dir: string, name: string): void {
    const files = readdirSync(dir).sort();
    let expected: string | null = null;

    for (const file of files) {
        const header = readFileSync(path.join(dir, file)).subarray(16, 24);
        const size = `${header.readUInt32BE(0)}x${header.readUInt32BE(4)}`;
        expected ??= size;

        if (size !== expected) {
            throw new Error(
                `GIF "${name}": frame ${file} is ${size} but the first frame is ${expected}. ` +
                    'ffmpeg would silently drop the mismatched frames. Keep the capture canvas ' +
                    'constant — to show breakpoints, resize an iframe inside a fixed viewport ' +
                    '(see responsiveViewportFrame) rather than calling page.setViewportSize().',
            );
        }
    }
}

/**
 * Replace the page with a fixed-size stage hosting the given URL in an iframe.
 *
 * The iframe's own width drives the CSS media queries inside it, so breakpoint
 * behaviour can be demonstrated without ever changing the browser viewport —
 * which keeps every captured frame the same size (see above). This is also how
 * the plugin's own admin preview panel does its resolution switcher.
 */
export async function responsiveViewportFrame(page: Page, url: string, height: number): Promise<void> {
    await page.goto('/');
    await page.evaluate(
        ({ src, stageHeight }) => {
            document.body.innerHTML = '';
            document.body.style.cssText =
                'margin:0;background:#0f172a;display:flex;align-items:center;justify-content:center;';
            document.documentElement.style.cssText = 'background:#0f172a;';

            const frame = document.createElement('iframe');
            frame.id = 'docs-viewport';
            frame.src = src;
            frame.style.cssText = `border:0;background:#fff;height:${stageHeight}px;width:1400px;transition:width .25s ease;box-shadow:0 0 0 1px rgba(255,255,255,.15);`;
            document.body.appendChild(frame);
        },
        { src: url, stageHeight: height },
    );

    await page.waitForFunction(() => {
        const frame = document.querySelector<HTMLIFrameElement>('#docs-viewport');

        return frame?.contentDocument?.readyState === 'complete';
    });
}

/** Set the stage iframe's width (i.e. the simulated device width). */
export function setStageWidth(page: Page, width: number): () => Promise<void> {
    return async () => {
        await page.evaluate((w) => {
            const frame = document.querySelector<HTMLIFrameElement>('#docs-viewport');
            if (frame) {
                frame.style.width = `${w}px`;
            }
        }, width);
        // Let the width transition finish and the iframe re-layout.
        await page.waitForFunction(
            (w) => document.querySelector<HTMLIFrameElement>('#docs-viewport')?.getBoundingClientRect().width === w,
            width,
        );
    };
}
