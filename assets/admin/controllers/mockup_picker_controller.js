import { Controller } from '@hotwired/stimulus';

/*
 * Style-preset form helper: picks a bundled mockup image into the hidden
 * mockupImage field and toggles the type-dependent blocks (source slides,
 * capture-from selectors) between slide and slider presets.
 */
export default class extends Controller {
    static targets = ['option', 'typeField', 'valueField', 'sliderOnly', 'slideCapture', 'sliderCapture'];

    connect() {
        this.refresh();
    }

    select(event) {
        if (!this.hasValueFieldTarget) {
            return;
        }

        this.valueFieldTarget.value = event.target.value;
        this.valueFieldTarget.dispatchEvent(new Event('change', { bubbles: true }));
    }

    refresh() {
        const type = this.hasTypeFieldTarget ? this.typeFieldTarget.value : 'slide';
        const isSlider = type === 'slider';

        if (this.hasSliderOnlyTarget) {
            this.sliderOnlyTarget.classList.toggle('d-none', !isSlider);
        }
        if (this.hasSlideCaptureTarget) {
            this.slideCaptureTarget.classList.toggle('d-none', isSlider);
        }
        if (this.hasSliderCaptureTarget) {
            this.sliderCaptureTarget.classList.toggle('d-none', !isSlider);
        }
    }
}
