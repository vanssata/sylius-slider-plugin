import { Controller } from '@hotwired/stimulus';
import { applyPresetFields } from '../utils/apply_preset_fields.js';

// Applies a configured style preset from the preview-panel dropdown; the
// actual field filling lives in utils/apply_preset_fields.js (shared with
// the creation gallery).
export default class extends Controller {
    static values = {
        presets: Object,
    };

    apply(event) {
        const preset = this.presetsValue[event.params.preset];
        if (!preset || !preset.fields) {
            return;
        }

        applyPresetFields(preset.fields);
    }
}
