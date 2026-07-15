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
        button.innerHTML = copied > 0 ? '<i class="bi bi-check-lg me-1"></i> Copied' : 'Nothing to copy';
        button.disabled = true;
        this.flashTimer = window.setTimeout(() => {
            button.innerHTML = originalHtml;
            button.disabled = false;
            this.flashTimer = null;
        }, 1500);
    }
}
