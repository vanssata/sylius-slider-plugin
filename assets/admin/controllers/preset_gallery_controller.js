import { Controller } from '@hotwired/stimulus';
import { applyPresetFields } from '../utils/apply_preset_fields.js';

/*
 * Creation-page preset gallery: a Bootstrap modal listing "Blank" plus all
 * style presets (config + database). The controller sits ON the modal
 * element (like the preview modal) so its actions keep working after the
 * modal is re-parented to <body> to escape page stacking contexts.
 *
 * Opens itself once when the create form loads; toolbar buttons with
 * data-bs-toggle/data-bs-target re-open it. Applying a preset fills the form
 * client-side (nothing persists until save); database slider presets with
 * source slides instead navigate to the create-from-preset route, where the
 * server clones the slides. Open/close goes through Bootstrap's delegated
 * data-bs-* attributes — the host admin page owns the Bootstrap instance.
 */
export default class extends Controller {
    static values = {
        presets: Object,
        autoOpen: { type: Boolean, default: false },
    };

    connect() {
        // Re-parenting fires disconnect+connect once; the parent check keeps
        // it from looping, the static flag keeps auto-open single-shot.
        if (this.element.parentElement !== document.body) {
            const stale = document.getElementById(this.element.id);
            if (stale && stale !== this.element) {
                stale.remove();
            }
            document.body.appendChild(this.element);

            return;
        }

        // Arriving from a grid page's choose-mode gallery: ?preset=<code>
        // means the admin already picked — apply it to the fresh form
        // instead of opening the gallery again.
        const chosen = new URLSearchParams(window.location.search).get('preset');
        if (chosen && this.presetsValue[chosen]) {
            if (!this.constructor.appliedFromUrl) {
                this.constructor.appliedFromUrl = true;
                requestAnimationFrame(() => applyPresetFields(this.presetsValue[chosen].fields ?? {}));
            }

            return;
        }

        if (this.autoOpenValue && !this.constructor.openedOnce) {
            this.constructor.openedOnce = true;
            requestAnimationFrame(() => this.openViaDelegatedTrigger());
        }
    }

    apply(event) {
        const preset = this.presetsValue[event.params.preset];
        if (preset && preset.fields) {
            applyPresetFields(preset.fields);
        }
    }

    openViaDelegatedTrigger() {
        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.hidden = true;
        trigger.dataset.bsToggle = 'modal';
        trigger.dataset.bsTarget = `#${this.element.id}`;
        document.body.appendChild(trigger);
        trigger.click();
        trigger.remove();
    }
}
