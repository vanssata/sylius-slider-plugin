import { Controller } from '@hotwired/stimulus';
import Pickr from '@simonwep/pickr';
import '@simonwep/pickr/dist/themes/classic.min.css';
import '@simonwep/pickr/dist/themes/monolith.min.css';
import '@simonwep/pickr/dist/themes/nano.min.css';

export default class extends Controller {
    static targets = ['button', 'input'];
    static values = {
        theme: {
            type: String,
            default: 'classic',
        },
        swatches: {
            type: Array,
            default: [],
        },
        options: {
            type: Object,
            default: {},
        },
    };

    connect() {
        // Moving this field elsewhere in the document (e.g. the preview
        // modal's settings-mount reparenting a real edit-form fieldset)
        // disconnects and reconnects this controller even though the
        // element never left the document. If the disconnect below skipped
        // teardown, a `.pickr` wrapper is already here — Pickr consumed the
        // original button target when it first initialized, so re-running
        // Pickr.create() would crash looking for a target that no longer
        // exists as such (and would double-init the widget either way).
        if (this.element.querySelector('.pickr')) {
            return;
        }

        this.onSaveBound = (color) => this.onSave(color);
        this.onClearBound = () => this.onClear();

        const options = this.buildPickrOptions();
        this.picker = Pickr.create(options);
        this.picker.on('save', this.onSaveBound);
        this.picker.on('clear', this.onClearBound);

        this.inputTarget.addEventListener('input', this.refreshSwatchBound = () => this.updateSwatch());
        this.inputTarget.addEventListener('change', this.refreshSwatchBound);
        this.updateSwatch();
    }

    disconnect() {
        // Only tear down on a genuine removal from the document. A reparent
        // still fires disconnect(), but the move is a synchronous DOM
        // operation that completes before any observer callback runs, so
        // isConnected already reflects the final (still-attached) position
        // by the time this executes — destroying the picker here would lose
        // it permanently, since Pickr consumes its anchor element and
        // connect() can't rebuild that on a plain reconnect.
        if (this.element.isConnected) {
            return;
        }

        if (this.picker) {
            this.picker.off('save', this.onSaveBound);
            this.picker.off('clear', this.onClearBound);
            this.picker.destroyAndRemove();
        }

        if (this.refreshSwatchBound) {
            this.inputTarget.removeEventListener('input', this.refreshSwatchBound);
            this.inputTarget.removeEventListener('change', this.refreshSwatchBound);
        }
    }

    buildPickrOptions() {
        const runtimeOptions = this.optionsValue || {};
        const onlyPredefinedSwatches = runtimeOptions.onlyPredefinedSwatches === true;
        const allowedSwatches = Array.isArray(runtimeOptions.allowedSwatches) && runtimeOptions.allowedSwatches.length > 0
            ? runtimeOptions.allowedSwatches
            : this.swatchesValue;

        const sanitizedRuntimeOptions = { ...runtimeOptions };
        delete sanitizedRuntimeOptions.onlyPredefinedSwatches;
        delete sanitizedRuntimeOptions.allowedSwatches;

        const baseOptions = {
            el: this.buttonTarget,
            theme: this.themeValue,
            default: this.inputTarget.value || null,
            swatches: this.swatchesValue,
            components: {
                preview: true,
                opacity: true,
                hue: true,
                interaction: {
                    hex: true,
                    rgba: true,
                    hsla: true,
                    hsva: true,
                    cmyk: true,
                    input: true,
                    clear: true,
                    save: true,
                },
            },
        };

        const mergedOptions = this.deepMerge(baseOptions, sanitizedRuntimeOptions);

        if (onlyPredefinedSwatches) {
            mergedOptions.swatches = allowedSwatches;
            mergedOptions.components = {
                preview: false,
                opacity: false,
                hue: false,
                interaction: {
                    clear: true,
                    save: true,
                },
            };
        }

        // Enforce form widget integration targets even if overridden in custom options.
        mergedOptions.el = this.buttonTarget;
        if (null === mergedOptions.default || '' === mergedOptions.default) {
            mergedOptions.default = this.inputTarget.value || null;
        }
        if (!Array.isArray(mergedOptions.swatches)) {
            mergedOptions.swatches = [];
        }

        this.onlyPredefinedSwatches = onlyPredefinedSwatches;
        this.allowedSwatches = allowedSwatches;

        return mergedOptions;
    }

    onSave(color) {
        this.inputTarget.value = '';

        if (color) {
            const resolvedColor = this.stringifyColor(color);
            if (this.onlyPredefinedSwatches) {
                const normalizedAllowed = new Set((this.allowedSwatches || []).map((item) => this.normalizeColor(item)));
                const normalizedResolved = this.normalizeColor(resolvedColor);

                if (normalizedAllowed.has(normalizedResolved)) {
                    this.inputTarget.value = resolvedColor;
                } else if (Array.isArray(this.allowedSwatches) && this.allowedSwatches.length > 0) {
                    this.inputTarget.value = this.allowedSwatches[0];
                }
            } else {
                this.inputTarget.value = resolvedColor;
            }
        }

        this.picker.hide();
        this.updateSwatch();
        this.inputTarget.dispatchEvent(new Event('input', { bubbles: true }));
        this.inputTarget.dispatchEvent(new Event('change', { bubbles: true }));
    }

    normalizeColor(value) {
        if (typeof value !== 'string') {
            return '';
        }

        return value.toLowerCase().replace(/\s+/g, '');
    }

    onClear() {
        this.inputTarget.value = '';
        this.updateSwatch();
        this.inputTarget.dispatchEvent(new Event('input', { bubbles: true }));
        this.inputTarget.dispatchEvent(new Event('change', { bubbles: true }));
    }

    stringifyColor(color) {
        const representation = this.picker?.getColorRepresentation?.()?.toUpperCase?.() || '';

        if (representation.startsWith('HEX')) {
            return color.toHEXA().toString();
        }

        if (representation === 'HSLA') {
            return color.toHSLA().toString();
        }

        if (representation === 'HSVA') {
            return color.toHSVA().toString();
        }

        if (representation === 'CMYK') {
            return color.toCMYK().toString();
        }

        return color.toRGBA().toString(0);
    }

    updateSwatch() {
        const color = this.inputTarget.value?.trim() || 'rgba(255, 255, 255, 1)';

        if (this.hasButtonTarget) {
            this.buttonTarget.style.background = color;
            this.buttonTarget.style.borderColor = color;

            return;
        }

        // Pickr consumed the original button target on init, replacing it
        // with its own .pcr-button (colored via the --pcr-color custom
        // property) — keep that in sync too, or values written directly to
        // the input (typing, presets, copy-from-desktop) never tint the
        // swatch.
        const pcrButton = this.element.querySelector('.pcr-button');
        if (pcrButton) {
            pcrButton.style.setProperty('--pcr-color', color);
        }
    }

    deepMerge(base, custom) {
        if (Array.isArray(base)) {
            return Array.isArray(custom) ? custom : base;
        }

        if (!this.isObject(base)) {
            return custom === undefined ? base : custom;
        }

        const result = { ...base };
        if (!this.isObject(custom)) {
            return result;
        }

        Object.keys(custom).forEach((key) => {
            const baseValue = result[key];
            const customValue = custom[key];

            if (Array.isArray(customValue)) {
                result[key] = customValue;
                return;
            }

            if (this.isObject(baseValue) && this.isObject(customValue)) {
                result[key] = this.deepMerge(baseValue, customValue);
                return;
            }

            result[key] = customValue;
        });

        return result;
    }

    isObject(value) {
        return value !== null && typeof value === 'object' && !Array.isArray(value);
    }
}
