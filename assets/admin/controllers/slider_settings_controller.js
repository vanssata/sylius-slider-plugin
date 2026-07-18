import { Controller } from '@hotwired/stimulus';

const OVERRIDE_GROUPS = ['media', 'layout', 'colors', 'effects', 'visibility'];

export default class extends Controller {
    static targets = [
        'linkingType',
        'translationEnabled',
        'showNavigation',
        'showArrows',
        'autoplayEnabled',
        'addButton',
        'overrideMedia',
        'overrideLayout',
        'overrideColors',
        'overrideEffects',
        'overrideVisibility',
    ];

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
        this.scopedAll('[data-slider-settings-button-only]').forEach((element) => {
            element.classList.toggle('d-none', !addButton);
        });
        this.applyItemGuard('button', addButton);

        OVERRIDE_GROUPS.forEach((name) => {
            const targetName = `override${name[0].toUpperCase()}${name.slice(1)}`;
            const hasTargetProp = `has${targetName[0].toUpperCase()}${targetName.slice(1)}Target`;
            const checked = this[hasTargetProp] ? this[`${targetName}Target`].checked : true;

            this.scopedAll(`[data-slider-settings-${name}-override-only]`).forEach((element) => {
                element.classList.toggle('d-none', !checked);
            });
            this.applyItemGuard(name, checked);
        });
    }

    // Disables (and force-collapses) accordion items marked with
    // data-slider-settings-item-guard="<name>" so they can only be opened
    // while their own "Overwrite" checkbox is checked.
    applyItemGuard(name, enabled) {
        this.scopedAll(`[data-slider-settings-item-guard="${name}"]`).forEach((item) => {
            const button = item.querySelector(':scope > .accordion-header .accordion-button');
            if (!button) {
                return;
            }

            button.disabled = !enabled;
            button.setAttribute('aria-disabled', String(!enabled));

            if (!enabled) {
                const collapse = item.querySelector(':scope > .accordion-collapse');
                if (collapse) {
                    collapse.classList.remove('show');
                }
                button.classList.add('collapsed');
                button.setAttribute('aria-expanded', 'false');
            }
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
