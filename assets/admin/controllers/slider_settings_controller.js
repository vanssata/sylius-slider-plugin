import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['linkingType', 'translationEnabled', 'showNavigation', 'showArrows', 'autoplayEnabled', 'addButton', 'overrideMedia', 'overrideSettings'];

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

        const addButton = this.hasAddButtonTarget ? this.addButtonTarget.checked : true;
        const overrideMedia = this.hasOverrideMediaTarget ? this.overrideMediaTarget.checked : true;
        const overrideSettings = this.hasOverrideSettingsTarget ? this.overrideSettingsTarget.checked : true;

        this.scopedAll('[data-slider-settings-button-only]').forEach((element) => {
            element.classList.toggle('d-none', !addButton);
        });

        this.scopedAll('[data-slider-settings-media-override-only]').forEach((element) => {
            element.classList.toggle('d-none', !overrideMedia);
        });

        this.scopedAll('[data-slider-settings-settings-override-only]').forEach((element) => {
            element.classList.toggle('d-none', !overrideSettings);
        });
    }

    // Like querySelectorAll on the controller element, but ignores elements
    // that belong to a nested slider-settings controller (e.g. per-locale
    // translation groups inside the main slide form).
    scopedAll(selector) {
        return [...this.element.querySelectorAll(selector)].filter((element) => {
            const owner = element.closest('[data-controller~="slider-settings"]');

            return owner === this.element || owner === null;
        });
    }

    linkingTypeChanged() {
        this.refresh();
    }

    translationEnabledChanged() {
        this.refresh();
    }
}
