import { Controller } from '@hotwired/stimulus';

/*
 * Toolbar-driven form visibility (edit pages only — create pages keep their
 * classic tabs since no preview toolbar exists there): the preview panel's
 * locale dropdown and breakpoint tabs are the single source of truth for
 * which form sections are visible/edited. The preview-frame controller
 * dispatches document-level `vanssa-preview:context` events; this controller
 * toggles `d-none` on marked sections. Fields are never detached or disabled
 * — the whole form always round-trips, so the draft/override machinery is
 * untouched.
 *
 * Markers:
 * - data-vanssa-context-breakpoint="desktop|tablet|mobile" — visible only
 *   when that breakpoint is selected in the preview toolbar.
 * - data-vanssa-context-locale="__base__" — visible only when the toolbar
 *   shows "Default" (base, non-translated fields).
 * - data-vanssa-context-locale="__locales__" — visible only when a concrete
 *   locale is selected (e.g. the Translations card shell).
 * - data-vanssa-context-locale="<locale>" — visible only for that locale.
 * - no attribute — always visible.
 *
 * Scope: events carry the dispatching preview instance's scope ('inline' or
 * the modal element id); this controller derives its own from its closest
 * .modal ancestor, so the inline panel and slide modals never cross-drive
 * each other's forms.
 */
export default class extends Controller {
    connect() {
        this.handleContext = this.handleContext.bind(this);
        document.addEventListener('vanssa-preview:context', this.handleContext);

        this.currentBreakpoint = 'desktop';
        this.currentLocale = 'default';
        this.applyVisibility();
    }

    disconnect() {
        document.removeEventListener('vanssa-preview:context', this.handleContext);
    }

    // NOTE: must not be called `scope` — that would shadow Stimulus's own
    // `scope` getter and break `this.element`.
    contextScope() {
        return this.element.closest('.modal')?.id ?? 'inline';
    }

    handleContext(event) {
        const detail = event.detail ?? {};
        if ((detail.scope ?? 'inline') !== this.contextScope()) {
            return;
        }

        if (detail.breakpoint) {
            this.currentBreakpoint = detail.breakpoint;
        }
        if (detail.locale) {
            this.currentLocale = detail.locale;
        }

        this.applyVisibility();
    }

    applyVisibility() {
        if (!this.element.isConnected) {
            return;
        }

        this.element.querySelectorAll('[data-vanssa-context-breakpoint]').forEach((section) => {
            section.classList.toggle('d-none', section.dataset.vanssaContextBreakpoint !== this.currentBreakpoint);
        });

        const isBase = this.currentLocale === 'default' || this.currentLocale === '';
        this.element.querySelectorAll('[data-vanssa-context-locale]').forEach((section) => {
            const marker = section.dataset.vanssaContextLocale;
            let visible;
            if (marker === '__base__') {
                visible = isBase;
            } else if (marker === '__locales__') {
                visible = !isBase;
            } else {
                visible = marker === this.currentLocale;
            }
            section.classList.toggle('d-none', !visible);
        });
    }
}
