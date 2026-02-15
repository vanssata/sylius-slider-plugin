import ColorPicker from '@stimulus-components/color-picker';
import '@simonwep/pickr/dist/themes/classic.min.css';

export default class extends ColorPicker {
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
    };

    onSave(color) {
        this.inputTarget.value = '';

        if (color) {
            this.inputTarget.value = color.toRGBA().toString(0);
        }

        this.picker.hide();
        this.updateSwatch();
        this.inputTarget.dispatchEvent(new Event('input', { bubbles: true }));
        this.inputTarget.dispatchEvent(new Event('change', { bubbles: true }));
    }

    connect() {
        super.connect();
        this.inputTarget.addEventListener('input', () => this.updateSwatch());
        this.inputTarget.addEventListener('change', () => this.updateSwatch());
        this.updateSwatch();
    }

    get swatches() {
        if (this.swatchesValue.length > 0) {
            return this.swatchesValue;
        }

        return super.swatches;
    }

    get componentOptions() {
        return {
            preview: true,
            hue: true,
            opacity: true,
            interaction: {
                input: true,
                clear: true,
                save: true,
            },
        };
    }

    updateSwatch() {
        if (!this.hasButtonTarget) {
            return;
        }

        const color = this.inputTarget.value?.trim() || 'rgba(255, 255, 255, 1)';
        this.buttonTarget.style.background = color;
        this.buttonTarget.style.borderColor = color;
    }
}
