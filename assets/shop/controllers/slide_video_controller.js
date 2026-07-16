import { Controller } from '@hotwired/stimulus';

/**
 * Plays a slide video only while it is actually visible: the video element
 * must be in the viewport (display:none breakpoint variants never intersect)
 * and its slide must be the active one. Works standalone too (banner pages),
 * where there is no surrounding slide.
 */
export default class extends Controller {
    connect() {
        this.inView = false;
        this.slide = this.element.closest('.vanssa-slide');
        this.sync = this.sync.bind(this);

        this.viewportObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                this.inView = entry.isIntersecting;
            });
            this.sync();
        }, { threshold: 0.2 });
        this.viewportObserver.observe(this.element);

        if (this.slide) {
            this.classObserver = new MutationObserver(this.sync);
            this.classObserver.observe(this.slide, { attributes: true, attributeFilter: ['class'] });
        }

        this.sync();
    }

    disconnect() {
        if (this.viewportObserver) {
            this.viewportObserver.disconnect();
            this.viewportObserver = null;
        }

        if (this.classObserver) {
            this.classObserver.disconnect();
            this.classObserver = null;
        }

        if (!this.element.paused) {
            this.element.pause();
        }
    }

    sync() {
        const slideActive = this.slide === null || this.slide.classList.contains('is-active');

        if (this.inView && slideActive) {
            const playPromise = this.element.play();
            if (playPromise) {
                playPromise.catch(() => {});
            }
        } else if (!this.element.paused) {
            this.element.pause();
        }
    }
}
