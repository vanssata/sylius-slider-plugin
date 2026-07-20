import { Controller } from '@hotwired/stimulus';

/**
 * Grows a single-row textarea to fit its content as the user types, so a title
 * field can start compact (rows="1") yet expand when a line break is entered.
 * Bound via `data-action="input->vanssa-textarea-autosize#resize"`; the initial
 * fit runs on connect so pre-filled multi-line values render at full height.
 */
export default class extends Controller {
    connect() {
        this.resize();
    }

    resize() {
        const el = this.element;
        el.style.height = 'auto';
        el.style.height = `${el.scrollHeight}px`;
    }
}
