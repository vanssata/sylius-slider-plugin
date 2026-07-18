import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'preview'];

    connect() {
        this.objectUrl = null;
        this.defaultBackgroundImage = this.hasPreviewTarget ? this.previewTarget.style.backgroundImage : '';
    }

    disconnect() {
        // A reparent (e.g. the preview modal's settings-mount) also fires
        // disconnect() even though the element never left the document —
        // only release the object URL on a genuine removal, or a
        // just-selected image preview would go blank after the move.
        if (this.element.isConnected) {
            return;
        }

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
