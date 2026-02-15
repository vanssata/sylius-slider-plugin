import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['previewBox', 'previewHeading', 'previewDescription'];

    connect() {
        this.refresh = this.refresh.bind(this);
        this.onShownTab = this.onShownTab.bind(this);
        this.scheduleRefresh = this.scheduleRefresh.bind(this);
        this.pendingRefresh = null;
        this.element.addEventListener('input', this.refresh);
        this.element.addEventListener('change', this.refresh);
        this.element.addEventListener('keyup', this.refresh);
        this.element.addEventListener('shown.bs.tab', this.onShownTab);
        this.observer = new MutationObserver(this.scheduleRefresh);
        this.observer.observe(this.element, { childList: true, subtree: true, attributes: true, characterData: true });
        this.refresh();
    }

    disconnect() {
        this.element.removeEventListener('input', this.refresh);
        this.element.removeEventListener('change', this.refresh);
        this.element.removeEventListener('keyup', this.refresh);
        this.element.removeEventListener('shown.bs.tab', this.onShownTab);
        if (this.observer) {
            this.observer.disconnect();
        }
        if (this.pendingRefresh) {
            window.cancelAnimationFrame(this.pendingRefresh);
            this.pendingRefresh = null;
        }
    }

    refresh() {
        if (!this.hasPreviewBoxTarget) {
            return;
        }

        const title = this.getValue('title', '');
        const descriptionText = this.getValue('description', '');
        const headingElement = this.getValue('headlineElement', 'h3');
        const horizontal = this.normalizeHorizontal(this.getValue('contentHorizontalPosition', 'start'));
        const vertical = this.normalizeVertical(this.getValue('contentVerticalPosition', 'bottom'));
        const textAlign = this.getValue('contentTextAlign', 'left');
        const textColor = this.getValue('textColor', 'rgba(255, 255, 255, 1)');
        const headingColor = this.getValue('headlineColor', '');
        const descriptionColor = this.getValue('descriptionColor', '');
        const backgroundColor = this.getValue('backgroundColor', 'rgba(15, 23, 42, 0.45)');
        const padding = this.getValue('contentPadding', '1rem');
        const margin = this.getValue('contentMargin', '0');
        const blurPreset = this.getValue('backgroundBlurPreset', 'none');
        const enableTextBlur = this.getCheckboxValue('enableTextBlur');
        const blurStrength = this.getValue('contentBlurStrength', '12');
        const borderRadius = this.getValue('borderRadius', '0');
        const headlineFontSize = this.getValue('headlineFontSize', '');
        const descriptionFontSize = this.getValue('descriptionFontSize', '');

        this.previewBoxTarget.classList.remove('is-pos-x-start', 'is-pos-x-center', 'is-pos-x-end', 'is-pos-y-top', 'is-pos-y-center', 'is-pos-y-bottom');
        this.previewBoxTarget.classList.add(`is-pos-x-${horizontal}`, `is-pos-y-${vertical}`);
        this.previewBoxTarget.style.textAlign = textAlign;
        this.previewBoxTarget.style.color = textColor;
        this.previewBoxTarget.style.background = backgroundColor || 'rgba(15, 23, 42, 0.45)';
        this.previewBoxTarget.style.padding = padding;
        this.previewBoxTarget.style.margin = margin;
        this.previewBoxTarget.style.borderRadius = `${parseInt(borderRadius, 10) || 0}px`;
        this.previewBoxTarget.style.backdropFilter = this.resolveBackdropFilter(blurPreset, enableTextBlur, blurStrength);

        if (this.hasPreviewHeadingTarget) {
            this.previewHeadingTarget.style.color = headingColor || '';
            this.previewHeadingTarget.style.fontSize = headlineFontSize || '';
            this.previewHeadingTarget.textContent = title || `Sample ${headingElement.toUpperCase()}`;
        }

        if (this.hasPreviewDescriptionTarget) {
            this.previewDescriptionTarget.style.color = descriptionColor || '';
            this.previewDescriptionTarget.style.fontSize = descriptionFontSize || '';
            this.previewDescriptionTarget.textContent = descriptionText || 'This preview reflects content alignment, colors, blur and spacing settings.';
        }
    }

    getValue(field, fallback = '') {
        const element = this.findField(field);

        if (!element) {
            return fallback;
        }

        const value = element.value;
        return value === '' || value === null || value === undefined ? fallback : value;
    }

    getCheckboxValue(field) {
        const element = this.findField(field);

        return Boolean(element && element.checked);
    }

    findField(field) {
        const activePane = this.element.querySelector('.tab-pane.active, .tab-pane.show');
        if (activePane) {
            const activeElement = activePane.querySelector(`[data-preview-field="${field}"]`);
            if (activeElement) {
                return activeElement;
            }
        }

        return this.element.querySelector(`[data-preview-field="${field}"]`);
    }

    onShownTab() {
        this.scheduleRefresh();
    }

    scheduleRefresh() {
        if (this.pendingRefresh) {
            return;
        }

        this.pendingRefresh = window.requestAnimationFrame(() => {
            this.pendingRefresh = null;
            this.refresh();
        });
    }

    normalizeHorizontal(value) {
        if (value === 'left' || value === 'start') {
            return 'start';
        }

        if (value === 'right' || value === 'end') {
            return 'end';
        }

        return 'center';
    }

    normalizeVertical(value) {
        if (value === 'top') {
            return 'top';
        }

        if (value === 'middle' || value === 'center') {
            return 'center';
        }

        return 'bottom';
    }

    resolveBackdropFilter(blurPreset, enableTextBlur, blurStrength) {
        const presetMap = {
            none: 'none',
            soft: 'blur(4px)',
            medium: 'blur(8px)',
            strong: 'blur(14px)',
        };

        if (enableTextBlur) {
            return `blur(${parseInt(blurStrength, 10) || 12}px)`;
        }

        return presetMap[blurPreset] ?? 'none';
    }
}
