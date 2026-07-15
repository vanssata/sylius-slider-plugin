import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['channel', 'locale', 'frame', 'frameWrapper', 'placeholder', 'sizeButton'];
    static values = {
        urlTemplate: String,
    };

    connect() {
        this.refresh();
    }

    refresh() {
        const channelCode = this.hasChannelTarget ? this.channelTarget.value : '';
        let localeCode = this.hasLocaleTarget ? this.localeTarget.value : '';

        if (channelCode !== '' && localeCode === '') {
            const selected = this.channelTarget.selectedOptions[0] ?? null;
            const defaultLocale = selected?.dataset.defaultLocale ?? '';
            if (defaultLocale !== '' && this.hasLocaleTarget) {
                this.localeTarget.value = defaultLocale;
                localeCode = defaultLocale;
            }
        }

        const ready = channelCode !== '' && localeCode !== '';

        if (this.hasPlaceholderTarget) {
            this.placeholderTarget.classList.toggle('d-none', ready);
        }

        if (this.hasFrameWrapperTarget) {
            this.frameWrapperTarget.classList.toggle('d-none', !ready);
        }

        if (!ready || !this.hasFrameTarget) {
            return;
        }

        const url = this.urlTemplateValue
            .replace('__CHANNEL__', encodeURIComponent(channelCode))
            .replace('__LOCALE__', encodeURIComponent(localeCode));

        if (this.frameTarget.getAttribute('src') !== url) {
            this.frameTarget.setAttribute('src', url);
        }
    }

    resize(event) {
        const width = event.params.width ?? '100%';

        if (this.hasFrameTarget) {
            this.frameTarget.style.width = width;
        }

        this.sizeButtonTargets.forEach((button) => {
            button.classList.toggle('active', button === event.currentTarget);
        });
    }
}
