import { Controller } from '@hotwired/stimulus';

/*
 * Effects group helper (per breakpoint): every animation type ships with
 * predefined settings; picking a type fills them in. A "customize" checkbox
 * reveals the settings that CAN be tuned for the selected type (defined in
 * the tunables map) — unchecking restores the type's defaults.
 *
 * Values:
 * - presets: { <animation>: { fields: {duration, delay}, tunables: [...] } }
 */
export default class extends Controller {
    static targets = ['type', 'custom', 'tunables', 'field'];

    static values = {
        presets: Object,
    };

    connect() {
        // Start "customized" when the stored values differ from the type's
        // predefined ones (the checkbox itself is UI-only, never submitted).
        if (this.hasCustomTarget) {
            this.customTarget.checked = !this.matchesPreset();
        }
        this.applyVisibility();
    }

    presetFor(type) {
        return this.presetsValue[type] ?? null;
    }

    currentType() {
        return this.hasTypeTarget ? this.typeTarget.value : 'none';
    }

    matchesPreset() {
        const preset = this.presetFor(this.currentType());
        if (!preset) {
            return true;
        }

        return this.fieldTargets.every((field) => {
            const expected = preset.fields?.[field.dataset.vanssaAnimationField];
            return expected === undefined || String(field.value) === String(expected);
        });
    }

    typeChanged() {
        if (!this.hasCustomTarget || !this.customTarget.checked) {
            this.applyPresetValues();
        }
        this.applyVisibility();
    }

    customChanged() {
        if (this.hasCustomTarget && !this.customTarget.checked) {
            this.applyPresetValues();
        }
        this.applyVisibility();
    }

    applyPresetValues() {
        const preset = this.presetFor(this.currentType());
        if (!preset) {
            return;
        }

        this.fieldTargets.forEach((field) => {
            const value = preset.fields?.[field.dataset.vanssaAnimationField];
            if (value === undefined) {
                return;
            }

            field.value = String(value);
            field.dispatchEvent(new Event('input', { bubbles: true }));
            field.dispatchEvent(new Event('change', { bubbles: true }));
        });
    }

    applyVisibility() {
        const preset = this.presetFor(this.currentType());
        const tunables = preset?.tunables ?? [];
        const customizable = tunables.length > 0;

        if (this.hasCustomTarget) {
            this.customTarget.closest('[data-vanssa-animation-custom-wrap]')?.classList.toggle('d-none', !customizable);
        }

        const showTunables = customizable && this.hasCustomTarget && this.customTarget.checked;
        this.tunablesTargets.forEach((wrap) => wrap.classList.toggle('d-none', !showTunables));

        this.fieldTargets.forEach((field) => {
            const allowed = tunables.includes(field.dataset.vanssaAnimationField);
            field.closest('[data-vanssa-animation-field-wrap]')?.classList.toggle('d-none', !allowed);
        });
    }
}
