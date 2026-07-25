import { Controller } from '@hotwired/stimulus';

// Drives one media slot of the slide form (see
// templates/admin/shared/form/media_upload_field.html.twig): previews the
// picked file in the tile, and lets the × on that tile mark the stored media
// for deletion — with an undo, since nothing is written until the form saves.
//
// Name kept as `image-upload-preview` although it now handles video slots too:
// renaming a controller means keeping four stimulus-bridge manifests in sync
// (see CLAUDE.md "Stimulus Controller Manifests").
export default class extends Controller {
    static targets = ['input', 'preview', 'tile', 'remove', 'url'];

    connect() {
        this.objectUrl = null;
        this.isVideo = this.element.dataset.vanssaMediaKind === 'video';
        // The saved state to restore on undo.
        this.defaultBackgroundImage = this.hasPreviewTarget && !this.isVideo ? this.previewTarget.style.backgroundImage : '';
        this.defaultVideoSrc = this.hasPreviewTarget && this.isVideo ? this.previewTarget.getAttribute('src') : null;
        this.defaultUrl = this.hasUrlTarget ? this.urlTarget.value : '';
        this.defaultEmpty = this.tileHasClass('is-empty');
        this.defaultUnpreviewable = this.tileHasClass('is-unpreviewable');
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
            // Un-picking a file reverts to the saved media — unless the slot
            // is flagged for removal, which is what should still be shown.
            if (!this.isFlaggedForRemoval()) {
                this.restoreSavedPreview();
            }

            return;
        }

        const [file] = input.files;
        const expectedType = this.isVideo ? 'video/' : 'image/';
        if (!file || !file.type.startsWith(expectedType)) {
            return;
        }

        // Picking a replacement is the opposite of removing the slot.
        this.setRemoveFlag(false);

        this.releaseObjectUrl();
        this.objectUrl = URL.createObjectURL(file);
        this.showPreview(this.objectUrl);
    }

    // The × on the tile: clears any pending upload, empties the external-URL
    // input (a video slot's URL would otherwise win over the removal) and
    // flags the slot so the form empties it on save.
    remove() {
        this.releaseObjectUrl();

        if (this.hasInputTarget) {
            this.inputTarget.value = '';
        }

        if (this.hasUrlTarget && this.urlTarget.value !== '') {
            this.urlTarget.value = '';
            this.urlTarget.dispatchEvent(new Event('input', { bubbles: true }));
        }

        this.clearPreview();
        this.setRemoveFlag(true);
        this.element.classList.add('is-media-removed');
    }

    undo() {
        this.restoreSavedPreview();

        if (this.hasUrlTarget) {
            this.urlTarget.value = this.defaultUrl;
            this.urlTarget.dispatchEvent(new Event('input', { bubbles: true }));
        }

        this.setRemoveFlag(false);
        this.element.classList.remove('is-media-removed');
    }

    // Keeps the live preview in step: the flag is part of the form snapshot
    // the preview frame re-renders from (preview_frame_controller.js).
    setRemoveFlag(checked) {
        if (!this.hasRemoveTarget || this.removeTarget.checked === checked) {
            return;
        }

        this.removeTarget.checked = checked;
        this.removeTarget.dispatchEvent(new Event('change', { bubbles: true }));
    }

    showPreview(src) {
        if (!this.hasPreviewTarget) {
            return;
        }

        if (this.isVideo) {
            this.previewTarget.setAttribute('src', src);
        } else {
            this.previewTarget.style.backgroundImage = `url('${src}')`;
        }

        this.setTileState({ empty: false, unpreviewable: false });
    }

    clearPreview() {
        if (!this.hasPreviewTarget) {
            return;
        }

        if (this.isVideo) {
            this.previewTarget.removeAttribute('src');
            this.previewTarget.load();
        } else {
            this.previewTarget.style.backgroundImage = '';
        }

        this.setTileState({ empty: true, unpreviewable: false });
    }

    restoreSavedPreview() {
        const savedSrc = this.isVideo ? this.defaultVideoSrc : this.defaultBackgroundImage;
        if (savedSrc) {
            if (this.isVideo) {
                this.showPreview(savedSrc);
            } else {
                this.previewTarget.style.backgroundImage = savedSrc;
                this.setTileState({ empty: false, unpreviewable: false });
            }

            return;
        }

        // Nothing playable was saved — which still covers an external video
        // (YouTube): a FILLED slot with only the icon placeholder to show.
        // Restoring both saved flags keeps its × reachable.
        this.clearPreview();
        this.setTileState({ empty: this.defaultEmpty, unpreviewable: this.defaultUnpreviewable });
    }

    isFlaggedForRemoval() {
        return this.element.classList.contains('is-media-removed');
    }

    tileHasClass(className) {
        return this.hasTileTarget && this.tileTarget.classList.contains(className);
    }

    setTileState({ empty, unpreviewable }) {
        if (!this.hasTileTarget) {
            return;
        }

        this.tileTarget.classList.toggle('is-empty', empty);
        this.tileTarget.classList.toggle('is-unpreviewable', unpreviewable);
    }

    releaseObjectUrl() {
        if (this.objectUrl) {
            URL.revokeObjectURL(this.objectUrl);
            this.objectUrl = null;
        }
    }
}
