import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['list', 'item', 'reorderTrigger'];

    connect() {
        this.draggedElement = null;
        this.lastSerializedOrder = this.serializeOrder();
    }

    onDragStart(event) {
        const item = event.currentTarget;
        if (!(item instanceof HTMLElement)) {
            return;
        }

        this.draggedElement = item;
        item.classList.add('is-dragging');

        if (event.dataTransfer) {
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', item.dataset.slideId || '');
        }
    }

    onDragOver(event) {
        event.preventDefault();

        const overElement = event.currentTarget;
        if (!(overElement instanceof HTMLElement) || overElement === this.draggedElement || this.draggedElement === null) {
            return;
        }

        const bounding = overElement.getBoundingClientRect();
        const insertAfter = event.clientY > bounding.top + bounding.height / 2;

        if (insertAfter) {
            overElement.after(this.draggedElement);
        } else {
            overElement.before(this.draggedElement);
        }
    }

    onDrop(event) {
        event.preventDefault();
        this.submitReorder();
    }

    onDragEnd() {
        if (this.draggedElement instanceof HTMLElement) {
            this.draggedElement.classList.remove('is-dragging');
        }

        this.draggedElement = null;
        this.submitReorder();
    }

    submitReorder() {
        if (!this.hasReorderTriggerTarget) {
            return;
        }

        const serializedOrder = this.serializeOrder();
        if (serializedOrder === '' || serializedOrder === this.lastSerializedOrder) {
            return;
        }

        this.lastSerializedOrder = serializedOrder;
        this.reorderTriggerTarget.dataset.liveOrderedSlideIdsParam = serializedOrder;
        this.reorderTriggerTarget.click();
    }

    serializeOrder() {
        if (!this.hasItemTarget) {
            return '';
        }

        return this.itemTargets
            .map((item) => item.dataset.slideId || '')
            .filter((value) => value !== '')
            .join(',');
    }
}
