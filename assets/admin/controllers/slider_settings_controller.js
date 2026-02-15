import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['linkingType', 'translationEnabled'];

    connect() {
        this.refresh();
    }

    refresh() {
        const linkingType = this.hasLinkingTypeTarget ? this.linkingTypeTarget.value : 'custom';

        this.element.querySelectorAll('[data-slider-settings-custom-only]').forEach((element) => {
            element.classList.toggle('d-none', linkingType !== 'custom');
        });

        this.element.querySelectorAll('[data-slider-settings-product-only]').forEach((element) => {
            element.classList.toggle('d-none', linkingType !== 'product');
        });
        const translationEnabled = this.hasTranslationEnabledTarget ? this.translationEnabledTarget.checked : true;
        this.element.querySelectorAll('[data-translation-settings-fields]').forEach((element) => {
            element.classList.toggle('d-none', !translationEnabled);
        });
    }

    linkingTypeChanged() {
        this.refresh();
    }

    translationEnabledChanged() {
        this.refresh();
    }
}
