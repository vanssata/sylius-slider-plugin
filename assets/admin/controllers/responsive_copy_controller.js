import { Controller } from '@hotwired/stimulus';

/**
 * Copies all responsive settings of the "desktop" breakpoint into another
 * breakpoint (tablet/mobile) of the same responsive settings group. Field
 * names only differ by the breakpoint segment: ...[responsive][desktop][x]
 * becomes ...[responsive][tablet][x].
 */
export default class extends Controller {
    connect() {
        this.flashTimer = null;
    }

    disconnect() {
        if (this.flashTimer !== null) {
            window.clearTimeout(this.flashTimer);
            this.flashTimer = null;
        }
    }

    copyFromDesktop(event) {
        const targetBreakpoint = event.params.breakpoint;
        if (!targetBreakpoint) {
            return;
        }

        const fields = this.element.querySelectorAll('input[name*="[desktop]"], select[name*="[desktop]"], textarea[name*="[desktop]"]');
        let copied = 0;

        fields.forEach((sourceField) => {
            const targetName = sourceField.name.replace('[desktop]', `[${targetBreakpoint}]`);
            const targetField = this.element.querySelector(`[name="${CSS.escape(targetName)}"]`);
            if (!targetField) {
                return;
            }

            if (sourceField.type === 'checkbox' || sourceField.type === 'radio') {
                targetField.checked = sourceField.checked;
            } else {
                targetField.value = sourceField.value;
            }

            targetField.dispatchEvent(new Event('input', { bubbles: true }));
            targetField.dispatchEvent(new Event('change', { bubbles: true }));
            copied += 1;
        });

        this.flashButton(event.currentTarget, copied);
    }

    flashButton(button, copied) {
        if (!button) {
            return;
        }

        const originalHtml = button.innerHTML;
        const checkmark = '<svg class="me-1" width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M13.854 3.646a.5.5 0 0 1 0 .708l-7 7a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L6.5 10.293l6.646-6.647a.5.5 0 0 1 .708 0z"/></svg>';
        button.innerHTML = copied > 0 ? checkmark + ' Copied' : 'Nothing to copy';
        button.disabled = true;
        this.flashTimer = window.setTimeout(() => {
            button.innerHTML = originalHtml;
            button.disabled = false;
            this.flashTimer = null;
        }, 1500);
    }
}
