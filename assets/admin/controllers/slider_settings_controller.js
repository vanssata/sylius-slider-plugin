import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['linkingType', 'translationEnabled', 'showNavigation', 'showArrows', 'autoplayEnabled'];

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

        const showNavigation = this.hasShowNavigationTarget ? this.showNavigationTarget.checked : true;
        const showArrows = this.hasShowArrowsTarget ? this.showArrowsTarget.checked : true;
        const autoplayEnabled = this.hasAutoplayEnabledTarget ? this.autoplayEnabledTarget.checked : true;

        this.element.querySelectorAll('[data-slider-settings-navigation-only]').forEach((element) => {
            element.classList.toggle('d-none', !showNavigation);
        });

        this.element.querySelectorAll('[data-slider-settings-arrows-only]').forEach((element) => {
            element.classList.toggle('d-none', !(showNavigation && showArrows));
        });

        this.element.querySelectorAll('[data-slider-settings-autoplay-only]').forEach((element) => {
            element.classList.toggle('d-none', !autoplayEnabled);
        });
    }

    linkingTypeChanged() {
        this.refresh();
    }

    translationEnabledChanged() {
        this.refresh();
    }
}
