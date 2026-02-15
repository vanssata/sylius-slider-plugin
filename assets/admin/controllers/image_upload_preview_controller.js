import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'preview'];

    connect() {
        this.objectUrl = null;
        this.defaultBackgroundImage = this.hasPreviewTarget ? this.previewTarget.style.backgroundImage : '';
    }

    disconnect() {
        this.releaseObjectUrl();
    }

    update() {
        if (!this.hasInputTarget || !this.hasPreviewTarget) {
            return;
        }

        const input = this.inputTarget;
        if (!input.files || input.files.length === 0) {
            this.releaseObjectUrl();
            this.previewTarget.style.backgroundImage = this.defaultBackgroundImage;

            return;
        }

        const [file] = input.files;
        if (!file || !file.type.startsWith('image/')) {
            return;
        }

        this.releaseObjectUrl();
        this.objectUrl = URL.createObjectURL(file);
        this.previewTarget.style.backgroundImage = `url('${this.objectUrl}')`;
    }

    releaseObjectUrl() {
        if (this.objectUrl) {
            URL.revokeObjectURL(this.objectUrl);
            this.objectUrl = null;
        }
    }
}
