import { Controller } from '@hotwired/stimulus';

/*
 * Re-parents a Bootstrap modal to <body> on connect. Modals rendered deep in
 * the page markup (inside the fixed settings drawer, accordions, …) sit in a
 * nested stacking context, so the body-level backdrop (z-index 1050) would
 * paint OVER them and swallow their clicks. Same fix the preview modals use.
 *
 * Also acts as the shell for the slide-browser modal: its footer Save button
 * calls save() (proxied to the LiveComponent's applyChanges), the component
 * answers with `vanssa:slide-browser-saved` to close the modal, and closing
 * the modal by any path discards unsaved marks (resetPending proxy).
 */
export default class extends Controller {
    connect() {
        this.onSaved = this.onSaved ?? (() => this.close());
        this.onHidden = this.onHidden ?? (() => this.clickProxy('reset'));
        // Re-rendering on open keeps the list in sync with changes made
        // outside the modal (unlink buttons, other sessions) — the
        // LiveComponent otherwise keeps showing its last render.
        this.onShown = this.onShown ?? (() => this.clickProxy('reset'));
        this.element.addEventListener('vanssa:slide-browser-saved', this.onSaved);
        this.element.addEventListener('hidden.bs.modal', this.onHidden);
        this.element.addEventListener('shown.bs.modal', this.onShown);

        if (this.element.parentElement === document.body) {
            return;
        }

        const stale = document.getElementById(this.element.id);
        if (stale && stale !== this.element) {
            stale.remove();
        }
        document.body.appendChild(this.element);
    }

    disconnect() {
        this.element.removeEventListener('vanssa:slide-browser-saved', this.onSaved);
        this.element.removeEventListener('hidden.bs.modal', this.onHidden);
        this.element.removeEventListener('shown.bs.modal', this.onShown);
    }

    save(event) {
        // Two Stimulus applications register this controller in the test
        // app — let only the first instance act on the click.
        event?.stopImmediatePropagation();
        this.clickProxy('apply');
    }

    close() {
        this.element.querySelector('.btn-close')?.click();
    }

    clickProxy(name) {
        this.element.querySelector(`[data-vanssa-browser-action="${name}"]`)?.click();
    }
}
