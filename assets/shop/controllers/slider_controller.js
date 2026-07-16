import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['slide', 'pagination', 'liveUpdate', 'prevButton', 'nextButton', 'progressBar'];
    static values = {
        options: Object,
    };

    connect() {
        this.currentIndex = 0;
        this.timer = null;
        this.totalSlides = this.slideTargets.length;
        this.parallaxWrapper = null;
        this.parallaxMoveBound = null;
        this.parallaxLeaveBound = null;
        this.keydownBound = null;
        this.pointerDownBound = null;
        this.pointerUpBound = null;
        this.pointerCancelBound = null;
        this.swipeStart = null;
        this.inView = false;
        this.viewportObserver = null;

        if (this.totalSlides <= 0) {
            return;
        }

        this.setupPagination();
        this.bindButtons();
        this.setupKeyboard();
        this.setupSwipe();
        this.applyCurrentSlide(0);
        this.setupViewportObserver();
        this.initParallax();

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

        if (this.viewportObserver) {
            this.viewportObserver.disconnect();
            this.viewportObserver = null;
        }

        this.teardownKeyboard();
        this.teardownSwipe();
        this.teardownParallax();
    }

    // Autoplay and content animations only run while the slider is actually
    // visible; the is-in-view class is what triggers the content animations.
    setupViewportObserver() {
        this.viewportObserver = new IntersectionObserver((entries) => {
            const isIntersecting = entries.some((entry) => entry.isIntersecting);
            if (isIntersecting === this.inView) {
                return;
            }

            this.inView = isIntersecting;
            this.element.classList.toggle('is-in-view', isIntersecting);

            if (isIntersecting) {
                this.startAutoplay();
            } else {
                this.stopAutoplay();
            }
        }, { threshold: 0.2 });

        this.viewportObserver.observe(this.element);
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
        const style = this.paginationStyle();
        this.slideTargets.forEach((_, index) => {
            const bullet = document.createElement('button');
            bullet.type = 'button';
            bullet.className = `vanssa-slider__bullet vanssa-slider__bullet--style-${style}`;
            if (style === 'dots') {
                bullet.classList.add(`vanssa-slider__bullet--${shape}`);
            }
            if (style === 'numbers') {
                bullet.textContent = `${index + 1}`;
            }
            bullet.setAttribute('aria-label', `Go to slide ${index + 1}`);
            bullet.dataset.index = `${index}`;
            bullet.addEventListener('click', () => this.goTo(index));
            this.paginationTarget.appendChild(bullet);
        });
    }

    paginationStyle() {
        const style = this.optionsValue?.paginationStyle ?? 'dots';

        return ['dots', 'lines', 'numbers'].includes(style) ? style : 'dots';
    }

    setupKeyboard() {
        if (this.optionsValue?.keyboardNavigation === false || this.totalSlides <= 1) {
            return;
        }

        this.keydownBound = (event) => {
            if (event.key === 'ArrowLeft') {
                event.preventDefault();
                this.previous();
            } else if (event.key === 'ArrowRight') {
                event.preventDefault();
                this.next();
            }
        };
        this.element.addEventListener('keydown', this.keydownBound);
    }

    teardownKeyboard() {
        if (this.keydownBound) {
            this.element.removeEventListener('keydown', this.keydownBound);
            this.keydownBound = null;
        }
    }

    setupSwipe() {
        if (this.optionsValue?.touchSwipe === false || this.totalSlides <= 1) {
            return;
        }

        this.pointerDownBound = (event) => {
            if (event.pointerType === 'mouse' || event.target.closest('a, button')) {
                this.swipeStart = null;

                return;
            }

            this.swipeStart = { x: event.clientX, y: event.clientY };
        };
        this.pointerUpBound = (event) => {
            if (!this.swipeStart) {
                return;
            }

            const deltaX = event.clientX - this.swipeStart.x;
            const deltaY = event.clientY - this.swipeStart.y;
            this.swipeStart = null;

            if (Math.abs(deltaX) < 40 || Math.abs(deltaX) <= Math.abs(deltaY)) {
                return;
            }

            if (deltaX > 0) {
                this.previous();
            } else {
                this.next();
            }
        };
        this.pointerCancelBound = () => {
            this.swipeStart = null;
        };

        this.element.addEventListener('pointerdown', this.pointerDownBound);
        this.element.addEventListener('pointerup', this.pointerUpBound);
        this.element.addEventListener('pointercancel', this.pointerCancelBound);
    }

    teardownSwipe() {
        if (this.pointerDownBound) {
            this.element.removeEventListener('pointerdown', this.pointerDownBound);
            this.pointerDownBound = null;
        }

        if (this.pointerUpBound) {
            this.element.removeEventListener('pointerup', this.pointerUpBound);
            this.pointerUpBound = null;
        }

        if (this.pointerCancelBound) {
            this.element.removeEventListener('pointercancel', this.pointerCancelBound);
            this.pointerCancelBound = null;
        }

        this.swipeStart = null;
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
        const supportedEffects = ['slide', 'fade', 'zoom', 'lift', 'flip'];
        const resolvedEffect = supportedEffects.includes(effect) ? effect : 'slide';

        this.slideTargets.forEach((slideElement, slideIndex) => {
            const isActive = slideIndex === this.currentIndex;
            slideElement.classList.toggle('is-active', isActive);
            supportedEffects.forEach((effectClass) => {
                slideElement.classList.toggle(`is-effect-${effectClass}`, effectClass === resolvedEffect);
            });
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

        this.resetParallax();
    }

    startAutoplay() {
        if (!this.inView || !this.autoplayEnabled() || this.totalSlides <= 1) {
            return;
        }

        this.stopAutoplay();

        this.timer = window.setInterval(() => {
            this.goTo(this.currentIndex + 1);
        }, this.autoplayInterval());

        this.renderProgress();
    }

    stopAutoplay() {
        if (this.timer === null) {
            return;
        }

        window.clearInterval(this.timer);
        this.timer = null;
        this.resetProgress();
    }

    renderProgress() {
        if (!this.hasProgressBarTarget || this.optionsValue?.showProgressBar !== true) {
            return;
        }

        const bar = this.progressBarTarget;
        bar.style.transition = 'none';
        bar.style.width = '0%';
        // Force a reflow so the width reset applies before the animation starts.
        void bar.offsetWidth;
        bar.style.transition = `width ${this.autoplayInterval()}ms linear`;
        bar.style.width = '100%';
    }

    resetProgress() {
        if (!this.hasProgressBarTarget) {
            return;
        }

        const bar = this.progressBarTarget;
        bar.style.transition = 'none';
        bar.style.width = '0%';
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

    initParallax() {
        if (!this.parallaxEnabled()) {
            return;
        }

        this.parallaxWrapper = this.element.querySelector('.vanssa-slider__wrapper');
        if (!this.parallaxWrapper) {
            return;
        }

        this.parallaxMoveBound = (event) => this.handleParallaxMove(event);
        this.parallaxLeaveBound = () => this.resetParallax();

        this.parallaxWrapper.addEventListener('pointermove', this.parallaxMoveBound);
        this.parallaxWrapper.addEventListener('pointerleave', this.parallaxLeaveBound);
    }

    teardownParallax() {
        if (this.parallaxWrapper && this.parallaxMoveBound) {
            this.parallaxWrapper.removeEventListener('pointermove', this.parallaxMoveBound);
        }

        if (this.parallaxWrapper && this.parallaxLeaveBound) {
            this.parallaxWrapper.removeEventListener('pointerleave', this.parallaxLeaveBound);
        }

        this.parallaxWrapper = null;
        this.parallaxMoveBound = null;
        this.parallaxLeaveBound = null;
        this.resetParallax();
    }

    handleParallaxMove(event) {
        if (!this.parallaxEnabled()) {
            return;
        }

        if (event.pointerType && event.pointerType !== 'mouse') {
            return;
        }

        if (!this.parallaxWrapper) {
            return;
        }

        const activeMedia = this.activeSlideMedia();
        if (!activeMedia) {
            return;
        }

        const rect = this.parallaxWrapper.getBoundingClientRect();
        if (rect.width <= 0 || rect.height <= 0) {
            return;
        }

        const relativeX = (event.clientX - rect.left) / rect.width;
        const relativeY = (event.clientY - rect.top) / rect.height;
        const strength = this.parallaxStrength();
        const shiftX = (0.5 - relativeX) * 2 * strength;
        const shiftY = (0.5 - relativeY) * 2 * strength;

        activeMedia.style.setProperty('--vanssa-slide-parallax-x', `${shiftX.toFixed(2)}px`);
        activeMedia.style.setProperty('--vanssa-slide-parallax-y', `${shiftY.toFixed(2)}px`);
    }

    resetParallax() {
        this.slideTargets.forEach((slideElement) => {
            const media = slideElement.querySelector('.vanssa-slide__media');
            if (!media) {
                return;
            }

            media.style.setProperty('--vanssa-slide-parallax-x', '0px');
            media.style.setProperty('--vanssa-slide-parallax-y', '0px');
        });
    }

    activeSlideMedia() {
        const slide = this.slideTargets[this.currentIndex] ?? null;
        if (!slide) {
            return null;
        }

        return slide.querySelector('.vanssa-slide__media');
    }

    parallaxEnabled() {
        if (this.parallaxStrength() <= 0) {
            return false;
        }

        if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return false;
        }

        if (window.matchMedia && window.matchMedia('(pointer: coarse)').matches) {
            return false;
        }

        return true;
    }

    parallaxStrength() {
        const raw = this.optionsValue?.parallax?.strength ?? '';
        const parsed = this.parseLengthToPx(raw);
        if (!Number.isFinite(parsed)) {
            return 0;
        }

        return Math.max(0, Math.min(200, parsed));
    }

    parseLengthToPx(value) {
        if (typeof value === 'number' && Number.isFinite(value)) {
            return value;
        }

        if (typeof value !== 'string') {
            return NaN;
        }

        const normalized = value.trim();
        if (normalized === '') {
            return NaN;
        }

        const match = normalized.match(/^([0-9]+(?:\.[0-9]+)?)(px|rem)$/i);
        if (!match) {
            return NaN;
        }

        const amount = Number(match[1]);
        const unit = match[2].toLowerCase();
        if (!Number.isFinite(amount)) {
            return NaN;
        }

        if (unit === 'px') {
            return amount;
        }

        const rootFontSize = Number(window.getComputedStyle(document.documentElement).fontSize.replace('px', ''));

        return Number.isFinite(rootFontSize) && rootFontSize > 0 ? amount * rootFontSize : amount * 16;
    }
}
