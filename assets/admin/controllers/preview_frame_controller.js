import { Controller } from '@hotwired/stimulus';

// Generic turbo-frame live-preview controller, shared by the Slider and
// Slide preview surfaces in two modes:
//
// - inline (edit pages): the panel sits directly on the page next to the
//   real edit form; every form change live-refreshes the preview via the
//   localStorage draft mechanism.
// - modal (grid rows, slide lists): the preview opens in a modal that also
//   lazy-loads the real edit form into a second turbo-frame (panelFrame)
//   from the edit-panel endpoint, so the slide can be edited and saved
//   without leaving the grid.
//
// A turbo-frame (not a classic iframe) is used so the preview renders in the
// same document as the admin page, sharing its DOM/JS realm.
export default class extends Controller {
    static targets = ['locale', 'channel', 'frame', 'frameWrapper', 'sizeButton', 'panelFrame', 'scaleBox', 'fullscreenEnter', 'fullscreenExit', 'drawerToggle'];
    static values = {
        urlTemplate: String,
        formSelector: String,
        draftKey: String,
        inline: Boolean,
        panelUrl: String,
    };

    connect() {
        this.handleFieldEvent = this.handleFieldEvent.bind(this);
        this.handleSubmit = this.handleSubmit.bind(this);
        this.handleShow = this.handleShow.bind(this);
        this.handleHide = this.handleHide.bind(this);
        this.handleFrameLoad = this.handleFrameLoad.bind(this);

        if (!this.inlineValue) {
            // The modal is rendered deep inside the page's markup, so its own
            // z-index only applies within that nested stacking context —
            // Bootstrap's backdrop (appended directly to <body>) would
            // otherwise always render on top of it. Moving the modal to be a
            // direct child of <body> fixes the stacking order. LiveComponent
            // re-renders (the slider's slide list) mount fresh modal copies
            // while the previously-moved one still sits on <body> — remove
            // those stale copies first or ids would collide.
            if (this.element.id) {
                document.body.querySelectorAll(`:scope > #${CSS.escape(this.element.id)}`).forEach((stale) => {
                    if (stale !== this.element) {
                        stale.remove();
                    }
                });
            }

            if (this.element.parentElement !== document.body) {
                document.body.appendChild(this.element);
            }

            this.element.addEventListener('show.bs.modal', this.handleShow);
            this.element.addEventListener('hide.bs.modal', this.handleHide);
        }

        this.form = this.hasFormSelectorValue ? document.querySelector(this.formSelectorValue) : null;

        // Listened on the document, not the form: in modal mode the form
        // arrives later (loaded into panelFrame) and is replaced on every
        // frame render, so element-bound listeners would go stale.
        // `event.target.form` / `event.target` filtering keeps each instance
        // scoped to its own form.
        document.addEventListener('input', this.handleFieldEvent);
        document.addEventListener('change', this.handleFieldEvent);
        document.addEventListener('submit', this.handleSubmit);
        document.addEventListener('turbo:frame-load', this.handleFrameLoad);

        // The test harness loads this controller's Stimulus registration
        // twice (once via the plugin's own entry, once via the app's shared
        // admin entry merging the plugin's controllers.json); an older,
        // still-registered copy may exist there and re-hide the wrapper.
        // Enforce visibility as an invariant rather than racing that
        // duplicate.
        if (this.hasFrameWrapperTarget) {
            this.frameWrapperObserver = new MutationObserver(() => {
                if (this.frameWrapperTarget.classList.contains('d-none')) {
                    this.frameWrapperTarget.classList.remove('d-none');
                }
            });
            this.frameWrapperObserver.observe(this.frameWrapperTarget, { attributes: true, attributeFilter: ['class'] });
            this.frameWrapperTarget.classList.remove('d-none');
        }

        // Scale-to-fit: the previewed slide keeps its natural render height;
        // re-fit whenever the frame's layout size changes (loads, drafts,
        // window resizes) — and whenever the wrapper's width changes (drawer
        // open/close, window resize): at fixed breakpoint widths the frame
        // itself does not resize, so observing it alone is not enough.
        if (this.hasFrameTarget && typeof ResizeObserver !== 'undefined') {
            this.frameResizeObserver = new ResizeObserver(() => this.rescale());
            this.frameResizeObserver.observe(this.frameTarget);
            if (this.hasFrameWrapperTarget) {
                this.frameResizeObserver.observe(this.frameWrapperTarget);
            }
        }

        if (this.inlineValue) {
            // The page just rendered the saved state; a stale draft from an
            // earlier abandoned session must not poison the first render.
            this.clearDraft();
            this.refresh();
            // Tell the form which locale/breakpoint the toolbar starts on —
            // deferred a frame so every form-context controller is connected.
            window.requestAnimationFrame(() => this.dispatchContext());
        }
    }

    disconnect() {
        document.removeEventListener('input', this.handleFieldEvent);
        document.removeEventListener('change', this.handleFieldEvent);
        document.removeEventListener('submit', this.handleSubmit);
        document.removeEventListener('turbo:frame-load', this.handleFrameLoad);

        if (!this.inlineValue) {
            this.element.removeEventListener('show.bs.modal', this.handleShow);
            this.element.removeEventListener('hide.bs.modal', this.handleHide);
        }

        if (this.frameWrapperObserver) {
            this.frameWrapperObserver.disconnect();
        }

        if (this.frameResizeObserver) {
            this.frameResizeObserver.disconnect();
        }

        if (this.escListener) {
            document.removeEventListener('keydown', this.escListener);
            this.escListener = null;
            document.body.classList.remove('vanssa-workspace-fullscreen');
        }

        window.clearTimeout(this.refreshTimeout);
    }

    // The slider edit page hosts BOTH an inline slider panel AND per-row
    // slide modals: document-level listeners need an ownership test so the
    // two kinds of instance never react to each other's DOM.
    ownsEventTarget(target) {
        if (!(target instanceof Element)) {
            return false;
        }

        if (this.inlineValue) {
            return !target.closest('.modal');
        }

        return this.element.contains(target);
    }

    handleShow() {
        // Fresh modal session: the panel form is fetched fresh below, so the
        // preview must start from the saved state, not a leftover draft.
        this.clearDraft();

        if (this.hasPanelFrameTarget && this.hasPanelUrlValue && this.panelUrlValue !== '') {
            this.panelFrameTarget.setAttribute('src', this.panelUrlValue);
        }

        this.refresh();
        this.dispatchContext();
    }

    handleHide() {
        // A debounced refresh() from a keystroke made just before closing
        // could otherwise fire after the teardown below.
        window.clearTimeout(this.refreshTimeout);

        // Unload the edit panel so its form (id "slide") never coexists with
        // another modal's — at most one loaded panel exists per document.
        if (this.hasPanelFrameTarget) {
            this.panelFrameTarget.removeAttribute('src');
            this.panelFrameTarget.removeAttribute('complete');
            this.panelFrameTarget.innerHTML = '';
        }

        this.form = this.hasFormSelectorValue ? document.querySelector(this.formSelectorValue) : null;
    }

    handleFieldEvent(event) {
        if (this.form && event.target.form === this.form) {
            this.snapshotDraft();
        }
    }

    handleSubmit(event) {
        if (this.form && event.target === this.form) {
            this.clearDraft();
            // The frame will re-render with the save response; refresh the
            // preview then (see handleFrameLoad) so it shows persisted state.
            this.pendingSaveRefresh = true;
        }
    }

    handleFrameLoad(event) {
        if (event.target === this.frameTarget) {
            // Our own preview frame just rendered new content — fit it and
            // clear the loading overlay.
            this.element.classList.remove('is-preview-pending');
            this.rescale();

            return;
        }

        if (!this.ownsEventTarget(event.target)) {
            return;
        }

        // The edit panel (or another owned frame) just rendered: its form is
        // a brand-new element, so re-resolve it. The preview only needs a
        // refresh when this render was a save response (draft already cleared
        // by handleSubmit — the refresh shows persisted state) or when an
        // unapplied draft is waiting; the initial panel load matches the
        // already-correct preview.
        this.form = this.hasFormSelectorValue ? document.querySelector(this.formSelectorValue) : null;

        // The freshly rendered form starts in its server-side default
        // visibility state — re-align it with the toolbar.
        this.dispatchContext();

        if (this.pendingSaveRefresh || this.readDraft() !== null) {
            this.pendingSaveRefresh = false;
            this.refresh();
        }
    }

    // Resolves the preview URL from the template: locale from the toolbar,
    // channel when a channel selector exists — otherwise the placeholder
    // parameter is dropped entirely so the server auto-resolves the channel.
    currentUrl() {
        const localeCode = this.hasLocaleTarget ? this.localeTarget.value : '';
        if (localeCode === '') {
            return null;
        }

        let url = this.urlTemplateValue.replace('__LOCALE__', encodeURIComponent(localeCode));
        if (this.hasChannelTarget) {
            url = url.replace('__CHANNEL__', encodeURIComponent(this.channelTarget.value));
        } else {
            url = url.replace(/[?&]channel=__CHANNEL__/, (match) => (match.startsWith('?') ? '?' : '')).replace(/\?$/, '');
        }

        // The server bakes the breakpoint into the rendered settings —
        // resizing the frame alone cannot trigger media queries, since the
        // preview shares the admin page's viewport.
        url += `${url.includes('?') ? '&' : '?'}breakpoint=${encodeURIComponent(this.currentBreakpoint())}`;

        return url;
    }

    currentBreakpoint() {
        const activeButton = this.sizeButtonTargets.find((b) => b.classList.contains('active'));

        return activeButton?.dataset.vanssaPreviewFrameBreakpointParam ?? 'desktop';
    }

    refresh() {
        const url = this.currentUrl();
        if (url === null || !this.hasFrameTarget) {
            return;
        }

        const draft = this.readDraft();

        if (draft === null) {
            if (this.frameTarget.getAttribute('src') !== url) {
                this.frameTarget.setAttribute('src', url);
            } else {
                this.frameTarget.reload();
            }

            return;
        }

        // The draft can be large (every locale/breakpoint's fields, serialized
        // as one form), so it's POSTed into the turbo-frame via a hidden form
        // rather than appended to the URL — a GET query string that big can
        // exceed the webserver's URI length limit and fail with 414.
        this.submitDraft(url, draft);
    }

    // A form is only intercepted frame-scoped (independent of Turbo Drive,
    // which is off — see entrypoint.js) when it's a DESCENDANT of the
    // target <turbo-frame>; a `data-turbo-frame` pointer from outside the
    // frame relies on Drive's own form-submit listener, which doesn't run
    // here. So this form is appended inside the frame right before
    // submitting — and rebuilt every time, since the frame's own content
    // (this form included) gets replaced whenever it next navigates.
    submitDraft(url, draft) {
        const form = document.createElement('form');
        form.method = 'post';
        form.style.display = 'none';
        form.action = url;

        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'overrides';
        input.value = draft;
        form.appendChild(input);

        this.frameTarget.appendChild(form);
        form.requestSubmit();
    }

    // The toolbar is the single source of truth for locale AND breakpoint:
    // picking a resolution tab or a language here drives which form sections
    // are visible/edited (see form_context_controller.js). The form has no
    // breakpoint tabs of its own on edit pages anymore.
    resize(event) {
        const breakpoint = event.params.breakpoint ?? 'desktop';
        this.applyBreakpoint(breakpoint, event.params.width ?? '100%');
    }

    changeLocale() {
        this.dispatchContext();
        this.refresh();
    }

    applyBreakpoint(breakpoint, width) {
        const button = this.sizeButtonTargets.find((b) => b.dataset.vanssaPreviewFrameBreakpointParam === breakpoint);
        const resolvedWidth = width ?? button?.dataset.vanssaPreviewFrameWidthParam ?? '100%';

        if (this.hasFrameTarget) {
            this.frameTarget.style.width = resolvedWidth;
        }

        this.sizeButtonTargets.forEach((b) => {
            b.classList.toggle('active', b === button);
        });

        this.dispatchContext(breakpoint);
        // Re-render at the new breakpoint (the URL carries it) — the width
        // change above only affects scaling, not the effective settings.
        this.element.classList.add('is-preview-pending');
        this.refresh();
        this.rescale();
    }

    // Fits the previewed page inside the wrapper: the frame keeps the
    // breakpoint's real width (so media queries apply faithfully) and is
    // transform-scaled down until both its width and full height are visible.
    // The scale box gets an explicit height because transforms don't affect
    // layout — without it the wrapper would keep the unscaled height.
    rescale() {
        if (!this.hasFrameTarget || !this.hasScaleBoxTarget || !this.hasFrameWrapperTarget) {
            return;
        }

        const frame = this.frameTarget;
        const wrapperWidth = this.frameWrapperTarget.clientWidth;
        const frameWidth = frame.offsetWidth;
        const contentHeight = frame.scrollHeight;
        if (wrapperWidth <= 0 || frameWidth <= 0 || contentHeight <= 0) {
            return;
        }

        const maxHeight = Number.parseFloat(window.getComputedStyle(this.frameWrapperTarget).maxHeight);
        const heightLimit = Number.isFinite(maxHeight) && maxHeight > 0 ? maxHeight : Number.POSITIVE_INFINITY;
        const scale = Math.min(1, wrapperWidth / frameWidth, heightLimit / contentHeight);

        frame.style.transformOrigin = 'top center';
        frame.style.transform = scale < 1 ? `scale(${scale})` : '';
        this.scaleBoxTarget.style.height = `${Math.ceil(contentHeight * scale)}px`;
    }

    // Preset try-on: hovering (or keyboard-focusing) a preset in the toolbar
    // dropdown temporarily renders it in the preview — the preset's field map
    // is merged OVER the current draft and POSTed as overrides, so the real
    // form and the stored draft are never touched. Mouse-out restores the
    // draft-or-saved state via a normal refresh.
    previewPreset(event) {
        window.clearTimeout(this.tryOnTimeout);
        const overrides = event.params.overrides ?? '';
        if (overrides === '') {
            return;
        }

        this.tryOnTimeout = window.setTimeout(() => {
            const url = this.currentUrl();
            if (url === null || !this.hasFrameTarget) {
                return;
            }

            const merged = new URLSearchParams(this.readDraft() ?? '');
            for (const [name, value] of new URLSearchParams(overrides)) {
                merged.set(name, value);
            }

            this.element.classList.add('is-preview-pending');
            this.submitDraft(url, merged.toString());
        }, 200);
    }

    endPresetPreview() {
        window.clearTimeout(this.tryOnTimeout);
        this.refresh();
    }

    // The settings drawer and the fullscreen workspace are ONE editing mode:
    // opening the settings takes the workspace fullscreen, and going
    // fullscreen brings the settings up — both buttons toggle the same
    // state, Escape leaves it. Inline panels only — modals are already
    // large and have no drawer.
    toggleDrawer(event) {
        // Duplicate-registration guard (two Stimulus apps in the test app
        // would toggle the state right back off).
        event?.stopImmediatePropagation();

        this.setEditingMode(!this.element.classList.contains('is-drawer-open'));
    }

    setEditingMode(active) {
        this.element.classList.toggle('is-drawer-open', active);
        document.body.classList.toggle('vanssa-workspace-fullscreen', active);
        this.drawerToggleTargets.forEach((button) => button.classList.toggle('active', active));

        if (active && !this.escListener) {
            this.escListener = (event) => {
                if (event.key === 'Escape') {
                    this.setEditingMode(false);
                }
            };
            document.addEventListener('keydown', this.escListener);
        } else if (!active && this.escListener) {
            document.removeEventListener('keydown', this.escListener);
            this.escListener = null;
        }

        if (this.hasFullscreenEnterTarget && this.hasFullscreenExitTarget) {
            this.fullscreenEnterTarget.classList.toggle('d-none', active);
            this.fullscreenExitTarget.classList.toggle('d-none', !active);
        }

        window.requestAnimationFrame(() => this.rescale());
    }

    toggleFullscreen(event) {
        event?.stopImmediatePropagation();

        this.setEditingMode(!document.body.classList.contains('vanssa-workspace-fullscreen'));
    }

    // Announces the toolbar's current locale + breakpoint to the owned form
    // (document-level: the modal's form lives in a lazily loaded frame). The
    // scope token keeps the inline panel and the slide modals from
    // cross-driving each other's forms.
    dispatchContext(breakpoint) {
        const activeButton = this.sizeButtonTargets.find((b) => b.classList.contains('active'));
        document.dispatchEvent(new CustomEvent('vanssa-preview:context', {
            detail: {
                scope: this.inlineValue ? 'inline' : (this.element.id || 'inline'),
                locale: this.hasLocaleTarget ? this.localeTarget.value : 'default',
                breakpoint: breakpoint ?? activeButton?.dataset.vanssaPreviewFrameBreakpointParam ?? 'desktop',
            },
        }));
    }

    // Unsaved edits don't exist on the server yet, so the preview frame
    // (a real page render) can't see them on its own — snapshot the current
    // form as a draft in localStorage and debounce a reload so the frame
    // picks it up shortly after the admin stops typing.
    snapshotDraft() {
        if (!this.form || !this.hasDraftKeyValue) {
            return;
        }

        const data = new URLSearchParams(new FormData(this.form)).toString();
        window.localStorage.setItem(this.draftKeyValue, data);

        // Show the preview loader through the debounce window too — Turbo's
        // own [busy] attribute only covers the fetch itself.
        this.element.classList.add('is-preview-pending');

        window.clearTimeout(this.refreshTimeout);
        this.refreshTimeout = window.setTimeout(() => this.refresh(), 500);
    }

    clearDraft() {
        if (this.hasDraftKeyValue) {
            window.localStorage.removeItem(this.draftKeyValue);
        }
    }

    readDraft() {
        if (!this.hasDraftKeyValue) {
            return null;
        }

        return window.localStorage.getItem(this.draftKeyValue);
    }
}
