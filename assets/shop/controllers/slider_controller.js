import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['slide', 'pagination', 'liveUpdate', 'prevButton', 'nextButton'];
    static values = {
        options: Object,
    };

    connect() {
        this.currentIndex = 0;
        this.timer = null;
        this.totalSlides = this.slideTargets.length;

        if (this.totalSlides <= 0) {
            return;
        }

        this.setupPagination();
        this.bindButtons();
        this.applyCurrentSlide(0);
        this.startAutoplay();

        if (this.autoplayPauseOnHover()) {
            this.element.addEventListener('mouseenter', this.stopAutoplayBound = () => this.stopAutoplay());
            this.element.addEventListener('mouseleave', this.startAutoplayBound = () => this.startAutoplay());
        }
    }

    disconnect() {
        this.stopAutoplay();

        if (this.stopAutoplayBound) {
            this.element.removeEventListener('mouseenter', this.stopAutoplayBound);
        }

        if (this.startAutoplayBound) {
            this.element.removeEventListener('mouseleave', this.startAutoplayBound);
        }
    }

    previous() {
        this.goTo(this.currentIndex - 1);
    }

    next() {
        this.goTo(this.currentIndex + 1);
    }

    goTo(index) {
        if (this.totalSlides <= 0) {
            return;
        }

        const maxIndex = this.totalSlides - 1;
        let nextIndex = index;
        const rewind = this.optionsValue?.rewind !== false;

        if (nextIndex < 0) {
            nextIndex = rewind ? maxIndex : 0;
        }

        if (nextIndex > maxIndex) {
            nextIndex = rewind ? 0 : maxIndex;
        }

        this.applyCurrentSlide(nextIndex);
        this.restartAutoplay();
    }

    setupPagination() {
        if (!this.hasPaginationTarget || this.totalSlides <= 1) {
            return;
        }

        this.paginationTarget.innerHTML = '';
        const shape = this.optionsValue?.paginationShape ?? 'circle';
        this.slideTargets.forEach((_, index) => {
            const bullet = document.createElement('button');
            bullet.type = 'button';
            bullet.className = `vanssa-slider__bullet vanssa-slider__bullet--${shape}`;
            bullet.setAttribute('aria-label', `Go to slide ${index + 1}`);
            bullet.dataset.index = `${index}`;
            bullet.addEventListener('click', () => this.goTo(index));
            this.paginationTarget.appendChild(bullet);
        });
    }

    bindButtons() {
        if (this.hasPrevButtonTarget) {
            this.prevButtonTarget.addEventListener('click', () => this.previous());
        }

        if (this.hasNextButtonTarget) {
            this.nextButtonTarget.addEventListener('click', () => this.next());
        }
    }

    applyCurrentSlide(index) {
        this.currentIndex = index;
        const effect = this.optionsValue?.effect ?? 'slide';

        this.slideTargets.forEach((slideElement, slideIndex) => {
            const isActive = slideIndex === this.currentIndex;
            slideElement.classList.toggle('is-active', isActive);
            slideElement.classList.toggle('is-effect-fade', effect === 'fade');
            slideElement.setAttribute('aria-hidden', isActive ? 'false' : 'true');
        });

        if (this.hasPaginationTarget) {
            [...this.paginationTarget.querySelectorAll('.vanssa-slider__bullet')].forEach((bullet, bulletIndex) => {
                bullet.classList.toggle('is-active', bulletIndex === this.currentIndex);
            });
        }

        if (this.hasLiveUpdateTarget) {
            this.liveUpdateTarget.textContent = `Slide ${this.currentIndex + 1} of ${this.totalSlides}`;
        }
    }

    startAutoplay() {
        if (!this.autoplayEnabled() || this.totalSlides <= 1) {
            return;
        }

        this.stopAutoplay();

        this.timer = window.setInterval(() => {
            this.goTo(this.currentIndex + 1);
        }, this.autoplayInterval());
    }

    stopAutoplay() {
        if (this.timer === null) {
            return;
        }

        window.clearInterval(this.timer);
        this.timer = null;
    }

    restartAutoplay() {
        if (!this.autoplayEnabled()) {
            return;
        }

        this.startAutoplay();
    }

    autoplayEnabled() {
        return this.optionsValue?.autoplay?.enabled === true;
    }

    autoplayInterval() {
        const interval = Number(this.optionsValue?.autoplay?.interval ?? 5000);

        return Number.isFinite(interval) && interval > 0 ? interval : 5000;
    }

    autoplayPauseOnHover() {
        return this.optionsValue?.autoplay?.pauseOnHover === true;
    }
}
