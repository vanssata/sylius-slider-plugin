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
        if (!this.hasButtonTarget) {
            return;
        }

        const color = this.inputTarget.value?.trim() || 'rgba(255, 255, 255, 1)';
        this.buttonTarget.style.background = color;
        this.buttonTarget.style.borderColor = color;
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
