import { defineConfig, devices } from '@playwright/test';

/**
 * Sylius PLUGIN layout: the only bootable kernel is `vendor/sylius/test-application`
 * (see composer.json `extra.public-dir` and behat.yml) — NOT `tests/TestApplication`,
 * which only holds config/templates merged into it.
 *
 * There is deliberately no `webServer` block. The docker stack IS the server:
 * the shared Traefik (`make proxy-up`) owns host :80 and routes
 * `sylius-slider.localhost` to this project's nginx, and the `playwright`
 * service joins the host network so that hostname resolves inside the
 * container exactly as it does on the host. From inside the compose network
 * the app would only be reachable as `http://nginx`, and Sylius resolves the
 * channel from the host — see docs/dev/local-domains.md.
 * `symfony server:start` is not an option — this host has no PHP.
 *
 * Set BASE_URL=http://localhost:82 to bypass the proxy and hit nginx directly.
 *
 * Entry points: `make e2e`, `make e2e-check SPEC=...`, `make docs-media`.
 */

const BASE_URL = process.env.BASE_URL ?? 'http://sylius-slider.localhost';

/** Storefront specs run at all three breakpoints; the admin workspace is a desktop tool. */
const RESPONSIVE_GLOB = 'shop/**/*.spec.ts';

export default defineConfig({
    testDir: './tests/e2e',
    // Sylius fixtures are a single shared dataset and several specs mutate it
    // (drag reorder, saving a slide). Parallel workers would race on it.
    fullyParallel: false,
    workers: 1,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 2 : 0,
    reporter: process.env.CI
        ? [['github'], ['html', { open: 'never' }]]
        : [['list'], ['html', { open: 'never' }]],
    outputDir: './test-results',
    timeout: 60_000,
    expect: { timeout: 10_000 },

    use: {
        baseURL: BASE_URL,
        // Pin locale, timezone and colour scheme: ARIA snapshots and screenshots
        // are order- and text-sensitive, and Sylius is multi-locale by design.
        locale: 'en-US',
        timezoneId: 'UTC',
        colorScheme: 'light',
        trace: 'on-first-retry',
        screenshot: 'only-on-failure',
        video: 'off',
    },

    projects: [
        {
            name: 'desktop',
            testIgnore: ['docs/**'],
            use: { ...devices['Desktop Chrome'], viewport: { width: 1400, height: 900 } },
        },
        {
            // 820x1180 is inside the template's `@media (max-width: 1024px)`
            // tablet band (templates/components/vanssa_sylius_slider/shop/slide.html.twig).
            name: 'tablet',
            testMatch: RESPONSIVE_GLOB,
            use: { ...devices['Desktop Chrome'], viewport: { width: 820, height: 1180 } },
        },
        {
            // 390x844 is inside the `@media (max-width: 767px)` mobile band.
            name: 'mobile',
            testMatch: RESPONSIVE_GLOB,
            use: { ...devices['Desktop Chrome'], viewport: { width: 390, height: 844 } },
        },
        {
            // Not tests — screenshot/GIF generators for docs/. These OVERWRITE
            // committed assets, so they must never run as part of an assertion
            // pass. Playwright has no "exclude from the default run" flag: a
            // bare `npx playwright test` selects EVERY configured project, so
            // the exclusion is enforced by `make e2e` naming its three
            // projects explicitly. Run these with `make docs-media`.
            name: 'docs-media',
            testDir: './tests/e2e/docs',
            use: { ...devices['Desktop Chrome'], viewport: { width: 1600, height: 950 } },
        },
    ],
});
