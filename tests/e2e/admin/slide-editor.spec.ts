/**
 * Regression this catches: a drag reorder of "Slides in this slider" re-renders
 * the LiveComponent at a moment when the per-row slide modals have already been
 * moved out of those rows and onto <body>. If a row ever loses its real `id`
 * attribute, idiomorph — which is what ux-live-component 2.31 morphs with, and
 * which pairs nodes by `id` ONLY (`data-live-id` is inert to it) — cannot pair
 * the incoming rows with the existing ones and appends them instead: the slider
 * then lists four slides twice, the next reorder POSTs every slide id twice, and
 * `removeSlide` fires from whichever duplicate the admin happened to click. The
 * same re-render tears down and re-mounts every colour field in the modal, which
 * is where a Pickr instance that survives its own `disconnect()` piles up.
 */
import { expect, test, type Locator, type Page } from '@playwright/test';

import { expectNoA11yViolations } from '../support/a11y';
import {
    loginAsAdmin,
    openSettingsDrawer,
    openSettingsSection,
    slideIdByCode,
    sliderIdByCode,
} from '../support/admin';
import { SLIDERS, SLIDES, routes } from '../support/data';

/** `#vanssa-slides-list` etc. are plugin-internal morph anchors — no ARIA equivalent exists. */
const SLIDES_LIST = '#vanssa-slides-list';
const REORDER_TRIGGER = '[data-vanssa-slider-slides-preview-target="reorderTrigger"]';
const HOISTED_MODALS = 'body > div[id^="vanssa-slide-preview-modal-"]';

type SlideRow = { id: string; slideId: string };
type PickerCensus = { fields: number; widgets: number; popups: number };

/**
 * The slider workspace keeps its settings accordion inside a drawer that is
 * display:none until the preview toolbar's "Settings" button opens it, so the
 * accordion headers are not in the accessibility tree before that click.
 */
async function openSlidesPanel(page: Page, sliderId: string): Promise<Locator> {
    await page.goto(routes.adminSliderEdit(sliderId));
    // Both helpers match exactly: "Settings" would otherwise also hit the
    // drawer's "Close settings" button and "Slides" its own "Add slides…".
    await openSettingsDrawer(page);
    await openSettingsSection(page, 'Slides');

    const list = page.locator(SLIDES_LIST);
    await expect(list).toBeVisible();

    return list;
}

async function readRows(rows: Locator): Promise<SlideRow[]> {
    return rows.evaluateAll<SlideRow[], HTMLElement>((elements) =>
        elements.map((element) => ({ id: element.id, slideId: element.dataset.slideId ?? '' })),
    );
}

async function readOrder(rows: Locator): Promise<string[]> {
    return (await readRows(rows)).map((row) => row.slideId);
}

/** Every `id` that more than one element in the document claims. */
async function duplicateElementIds(page: Page): Promise<string[]> {
    return page.evaluate(() => {
        const seen = new Set<string>();
        const duplicated = new Set<string>();
        document.querySelectorAll('[id]').forEach((element) => {
            if (seen.has(element.id)) {
                duplicated.add(element.id);
            }
            seen.add(element.id);
        });

        return [...duplicated].sort();
    });
}

function waitForReorderResponse(page: Page) {
    return page.waitForResponse((response) => response.url().includes('/_components/') && response.url().endsWith('/reorderSlides'));
}

/**
 * Real HTML5 drag, driven by dispatched DragEvents rather than by
 * `mouse.down()/move()/up()`: Chromium only synthesises dragstart/dragover/drop
 * for an OS-level drag session, which Playwright's input pipeline does not start
 * for a `draggable="true"` element, so a mouse-based drag silently does nothing
 * here. Dispatching the events straight at the rows still runs the exact
 * production path — slider_slides_preview_controller binds
 * dragstart/dragover/drop/dragend on each row via `data-action`, reorders the
 * DOM in `onDragOver`, and clicks the hidden reorderTrigger from `onDrop`.
 */
async function dragRowAfter(page: Page, sourceId: string, targetId: string): Promise<void> {
    const source = page.locator(`#${sourceId}`);
    const target = page.locator(`#${targetId}`);
    const box = await target.boundingBox();
    expect(box, `row #${targetId} should be laid out`).not.toBeNull();

    const dataTransfer = await page.evaluateHandle(() => new DataTransfer());
    const reordered = waitForReorderResponse(page);

    await source.dispatchEvent('dragstart', { dataTransfer });
    // clientY below the target's midpoint => the controller inserts AFTER it.
    await target.dispatchEvent('dragover', {
        dataTransfer,
        clientX: box!.x + box!.width / 2,
        clientY: box!.y + box!.height * 0.75,
    });
    await target.dispatchEvent('drop', { dataTransfer });
    await source.dispatchEvent('dragend', { dataTransfer });

    const response = await reordered;
    expect(response.ok(), 'the reorderSlides live action should succeed').toBe(true);
    await dataTransfer.dispose();
}

/**
 * Deterministic restore path: sets the same param the drag would have produced
 * on the component's own hidden reorder trigger and clicks it. The trigger is
 * `.d-none`, so it is clicked in-page rather than through Playwright's
 * actionability checks.
 */
async function reorderTo(page: Page, rows: Locator, slideIds: string[]): Promise<void> {
    const reordered = waitForReorderResponse(page);
    await page.locator(REORDER_TRIGGER).evaluate((element, order) => {
        (element as HTMLElement).dataset.liveOrderedSlideIdsParam = order;
        (element as HTMLElement).click();
    }, slideIds.join(','));
    await reordered;

    await expect.poll(() => readOrder(rows), { message: 'slide order should be restored' }).toEqual(slideIds);
}

async function readPickerCensus(page: Page): Promise<PickerCensus> {
    return page.evaluate(() => ({
        // One Stimulus root per colour field in the SlideType form…
        fields: document.querySelectorAll('[data-controller~="vanssa-rgba-color-picker"]').length,
        // …which Pickr replaces with its own `.pickr` root (third-party markup,
        // hence the class selectors)…
        widgets: document.querySelectorAll('.pickr').length,
        // …plus one `.pcr-app` panel appended straight to <body>.
        popups: document.querySelectorAll('body > .pcr-app').length,
    }));
}

/** Pickr initialises the fields in one Stimulus batch; poll instead of sleeping. */
async function settledPickerCensus(page: Page): Promise<PickerCensus> {
    let census: PickerCensus = { fields: 0, widgets: 0, popups: 0 };

    await expect
        .poll(
            async () => {
                census = await readPickerCensus(page);

                return census.fields > 0 && census.widgets === census.fields && census.popups === census.fields;
            },
            { message: `each colour field should own exactly one Pickr root and one body-level Pickr panel (last read: ${JSON.stringify(census)})` },
        )
        .toBe(true);

    return census;
}

test('slides list rows keep unique morph ids across a live re-render', async ({ page }) => {
    await loginAsAdmin(page);

    const sliderId = await sliderIdByCode(page, SLIDERS.classicArrows);
    const list = await openSlidesPanel(page, sliderId);
    const rows = list.locator('> article');

    await expect(rows).toHaveCount(4);
    await expect(list).toMatchAriaSnapshot({ name: 'slides-list.aria.yml' });
    await expectNoA11yViolations(page, SLIDES_LIST);

    const before = await readRows(rows);
    // Idiomorph pairs nodes by `id`: every row needs one, and it must be unique.
    expect(before.map((row) => row.id)).toEqual(before.map((row) => `vanssa-slide-item-${row.slideId}`));
    expect(new Set(before.map((row) => row.id)).size).toBe(before.length);
    expect(await duplicateElementIds(page)).toEqual([]);

    // One hoisted modal per row — the very DOM mutation that makes the morph
    // hard: each row's modal is moved to <body> after the server rendered it
    // inside the row.
    await expect(page.locator(HOISTED_MODALS)).toHaveCount(4);

    const originalOrder = before.map((row) => row.slideId);
    const rotatedOrder = [...originalOrder.slice(1), originalOrder[0]];

    try {
        await dragRowAfter(page, before[0].id, before[before.length - 1].id);

        // The re-render must MORPH the four rows, not append four more.
        await expect(rows).toHaveCount(4);
        await expect(page.locator(HOISTED_MODALS)).toHaveCount(4);
        expect(await duplicateElementIds(page)).toEqual([]);

        const after = await readRows(rows);
        expect(after.map((row) => row.slideId)).toEqual(rotatedOrder);
        expect(new Set(after.map((row) => row.id)).size).toBe(after.length);
        expect(new Set(after.map((row) => row.id))).toEqual(new Set(before.map((row) => row.id)));

        // …and it must have been the server that reordered, not just the local
        // drag: a fresh page load has to agree.
        const reloadedRows = (await openSlidesPanel(page, sliderId)).locator('> article');
        expect(await readOrder(reloadedRows)).toEqual(rotatedOrder);
    } finally {
        // Idempotence: the fixture order is shared with every other spec, and
        // two of them (admin/preview, shop/responsive-overrides) assert on the
        // ACTIVE slide — which is whichever one ends up first.
        //
        // Swallowed rather than thrown: reorderTo has an expect.poll inside it,
        // and an exception out of a finally block replaces the real failure
        // with a cleanup timeout. The assertion after the block keeps the teeth
        // on the happy path, and the other two specs now guard their own
        // premise explicitly, so a failed restore is reported where it is
        // understandable rather than three files away.
        await reorderTo(page, page.locator(SLIDES_LIST).locator('> article'), originalOrder).catch(
            (error: unknown) => {
                console.warn(`cleanup: could not restore the slide order: ${String(error)}`);
            },
        );
    }

    // The order this spec found on entry is the canonical fixture order — the
    // slides-list.aria.yml snapshot above pins it by name, so getting this far
    // means `originalOrder` really is what the next spec expects.
    expect(await readOrder(page.locator(SLIDES_LIST).locator('> article'))).toEqual(originalOrder);
});

test('the slides grid pencil opens a body-level modal with the real slide form and live preview', async ({ page }) => {
    await loginAsAdmin(page);

    const slideId = await slideIdByCode(page, SLIDES.newCollection);
    await page.goto(routes.adminSlideIndex);

    const modal = page.locator(`div#vanssa-slide-preview-modal-${slideId}`);
    await expect(modal).toHaveCount(1);
    // The portal move happens on Stimulus connect, i.e. before any click, and
    // stale copies of the same id are removed rather than left behind. Polled:
    // `toHaveCount(1)` only waits for the SERVER-rendered element, so a one-shot
    // read here races the controller booting on a cold bundle.
    await expect
        .poll(() => modal.evaluate((element) => element.parentElement === document.body), {
            message: 'modal_portal_controller should re-parent the modal to <body> on connect',
        })
        .toBe(true);

    await page
        .getByRole('row')
        .filter({ hasText: SLIDES.newCollection })
        .getByRole('button', { name: 'Edit' })
        .click();

    // Bootstrap only promotes a `.modal` to role=dialog while it is shown, so
    // this role locator resolving at all proves the hoisted element is the live
    // one (a stale copy left in the grid row would never be promoted).
    const dialog = page.getByRole('dialog', { name: 'Edit slide' });
    await expect(dialog).toBeVisible();
    expect(await dialog.evaluate((element) => element.parentElement === document.body)).toBe(true);

    // Bootstrap's modal header carries no landmark role, hence the CSS scope.
    await expect(dialog.locator('.modal-header')).toMatchAriaSnapshot(`
        - heading "Edit slide" [level=1]
        - link "Open full editor":
          - /url: /\\/admin\\/slides\\/\\d+\\/edit/
        - button "Close"
    `);

    // The real SlideType form, lazily fetched into the modal's second
    // turbo-frame. `#slide` is the id the footer's form="slide" points at.
    const form = dialog.locator('form#slide');
    await expect(form).toBeVisible();
    await expect(form.getByRole('textbox', { name: 'Code' })).toHaveValue(SLIDES.newCollection);
    await expect(dialog.getByRole('button', { name: 'Update' })).toBeVisible();

    // …and a live preview that is an actual storefront render of this slide.
    const preview = dialog.locator(`#vanssa-slide-preview-frame-${slideId}`);
    await expect(preview.getByRole('region', { name: 'New Collection' })).toBeVisible();
    await expect(preview.locator('[data-slide-code]')).toHaveAttribute('data-slide-code', SLIDES.newCollection);

    // Read-only test: nothing is submitted, so closing restores the start state.
    await dialog.getByRole('button', { name: 'Close' }).first().click();
    await expect(modal).toBeHidden();
});

test('reopening the slide editor modal does not leak colour pickers', async ({ page }) => {
    await loginAsAdmin(page);

    const slideId = await slideIdByCode(page, SLIDES.newCollection);
    await page.goto(routes.adminSlideIndex);

    const modal = page.locator(`div#vanssa-slide-preview-modal-${slideId}`);
    const editButton = page
        .getByRole('row')
        .filter({ hasText: SLIDES.newCollection })
        .getByRole('button', { name: 'Edit' });

    // The form frame is empty until show.bs.modal fetches it, so nothing exists yet.
    expect(await readPickerCensus(page)).toEqual({ fields: 0, widgets: 0, popups: 0 });

    const censuses: PickerCensus[] = [];
    for (const pass of [1, 2]) {
        await editButton.click();
        await expect(modal).toBeVisible();
        await expect(modal.locator('form#slide')).toBeVisible();

        censuses.push(await settledPickerCensus(page));

        await modal.getByRole('button', { name: 'Close' }).first().click();
        await expect(modal).toBeHidden();

        // Closing unloads the panel frame; the controller's disconnect() must
        // destroyAndRemove() every picker, including the body-level `.pcr-app`
        // panels it can no longer reach through the modal subtree.
        await expect
            .poll(() => readPickerCensus(page), { message: `pass ${pass}: closing the modal should destroy every Pickr instance` })
            .toEqual({ fields: 0, widgets: 0, popups: 0 });
    }

    // The classic leak: the second session doubles the number of pickers.
    expect(censuses[1]).toEqual(censuses[0]);
});
