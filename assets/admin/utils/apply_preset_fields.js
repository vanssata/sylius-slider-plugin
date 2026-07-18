// Fills form fields from a style preset's {fieldName: value} map (exact-name
// lookup) and dispatches bubbling input/change events so the rgba pickers,
// the slider-settings gating, and the live preview draft all react instantly.
// Non-destructive: nothing persists until the form is saved.
// Shared by preset_applier_controller (edit pages) and
// preset_gallery_controller (create pages).
export function applyPresetFields(fields) {
    for (const [name, value] of Object.entries(fields || {})) {
        const field = document.querySelector(`[name="${CSS.escape(name)}"]`);
        if (!field) {
            continue;
        }

        if (field.type === 'checkbox' || field.type === 'radio') {
            field.checked = value === true;
        } else {
            field.value = String(value);
        }

        field.dispatchEvent(new Event('input', { bubbles: true }));
        field.dispatchEvent(new Event('change', { bubbles: true }));
    }
}
