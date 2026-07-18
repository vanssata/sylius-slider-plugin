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

        // Per-breakpoint structural settings: apply the map matching the
        // viewport now and whenever it crosses a breakpoint boundary.
        this.mediaTablet = window.matchMedia('(max-width: 1024px)');
        this.mediaMobile = window.matchMedia('(max-width: 767px)');
        this.breakpointChangeBound = () => this.applyStructuralSettings();
        this.mediaTablet.addEventListener('change', this.breakpointChangeBound);
        this.mediaMobile.addEventListener('change', this.breakpointChangeBound);

        this.applyStructuralSettings(true);
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

        if (this.breakpointChangeBound) {
            this.mediaTablet?.removeEventListener('change', this.breakpointChangeBound);
            this.mediaMobile?.removeEventListener('change', this.breakpointChangeBound);
            this.breakpointChangeBound = null;
        }

        this.teardownKeyboard();
        this.teardownSwipe();
        this.teardownParallax();
    }

    currentBreakpoint() {
        if (this.mediaMobile?.matches) {
            return 'mobile';
        }
        if (this.mediaTablet?.matches) {
            return 'tablet';
        }

        return 'desktop';
    }

    // Effective structural settings for the current viewport (locale merged
    // server-side; tablet cascades from desktop, mobile from tablet).
    structural() {
        const maps = this.optionsValue?.responsive;

        return maps?.[this.currentBreakpoint()] ?? maps?.desktop ?? null;
    }

    applyStructuralSettings(initial = false) {
        const settings = this.structural();
        if (!settings) {
            return;
        }

        const el = this.element;
        const swapModifier = (prefix, value, allowed) => {
            allowed.forEach((candidate) => el.classList.toggle(prefix + candidate, candidate === value));
        };
        swapModifier('vanssa-slider--container-', settings.containerWidth === 'full' ? 'full' : 'content', ['content', 'full']);
        swapModifier('vanssa-slider--arrows-', settings.arrowsPosition, ['overlay', 'outside', 'bottom']);
        swapModifier('vanssa-slider--arrows-align-', settings.arrowsVerticalAlign, ['center', 'top', 'bottom']);
        swapModifier('vanssa-slider--pagination-', settings.paginationPosition, ['bottom-inside', 'bottom-outside', 'top', 'left', 'right']);

        for (const [property, value] of Object.entries({
            '--vanssa-slider-nav-size': settings.navigationSize,
            '--vanssa-slider-nav-shadow': settings.navigationShadow,
            '--vanssa-slider-nav-color': settings.navigationColor,
            '--vanssa-slider-nav-bg': settings.navigationBackgroundColor,
            '--vanssa-slider-pagination-size': settings.paginationSize,
            '--vanssa-slider-pagination-shadow': settings.paginationShadow,
            '--vanssa-slider-pagination-color': settings.paginationColor,
            '--vanssa-slider-pagination-active': settings.paginationActiveColor,
        })) {
            if (value) {
                el.style.setProperty(property, value);
            }
        }

        const controls = el.querySelector('.vanssa-slider__controls');
        if (controls) {
            controls.style.display = settings.showNavigation && settings.showArrows && this.totalSlides > 1 ? '' : 'none';
        }
        if (this.hasPaginationTarget) {
            this.paginationTarget.style.display = settings.showNavigation && this.totalSlides > 1 ? '' : 'none';
        }
        const progress = el.querySelector('.vanssa-slider__progress');
        if (progress) {
            progress.style.display = settings.showProgressBar && this.totalSlides > 1 ? '' : 'none';
        }

        el.querySelectorAll('.vanssa-slider__action').forEach((button) => {
            ['chevron', 'angle', 'square'].forEach((icon) => {
                button.classList.toggle(`vanssa-slider__action-icon--${icon}`, icon === settings.navigationIcon);
            });
            const isPrev = button.classList.contains('vanssa-slider__action--prev');
            button.textContent = settings.navigationIcon === 'square' ? '■' : (settings.navigationIcon === 'angle' ? (isPrev ? '❮' : '❯') : (isPrev ? '‹' : '›'));
        });

        if (!initial) {
            // Pagination style/shape and slide effect may differ — rebuild.
            this.setupPagination();
            this.applyCurrentSlide(this.currentIndex);
        }
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
        const shape = this.structural()?.paginationShape ?? this.optionsValue?.paginationShape ?? 'circle';
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
        const style = this.structural()?.paginationStyle ?? this.optionsValue?.paginationStyle ?? 'dots';

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
        const effect = this.structural()?.slideEffect ?? this.optionsValue?.effect ?? 'slide';
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

        // Video-gated advance: when the active slide shows an auto-playing
        // video, its `ended` event drives the advance instead of the fixed
        // interval — the interval only remains as a fallback in case the
        // video never starts (load failure, blocked autoplay). Click-mode
        // videos never gate rotation (an unstarted video must not stall it).
        if (this.activeAutoplayMedia()) {
            this.mediaEndedBound = (event) => {
                if (this.ownsActiveSlideEvent(event)) {
                    this.goTo(this.currentIndex + 1);
                }
            };
            this.mediaPlayingBound = (event) => {
                if (!this.ownsActiveSlideEvent(event)) {
                    return;
                }

                window.clearTimeout(this.timer);
                this.timer = null;
                this.renderProgress(event.detail?.remainingMs ?? null);
            };
            this.element.addEventListener('vanssa-slide-video:ended', this.mediaEndedBound);
            this.element.addEventListener('vanssa-slide-video:playing', this.mediaPlayingBound);
        }

        this.timer = window.setTimeout(() => {
            this.goTo(this.currentIndex + 1);
        }, this.autoplayInterval());

        this.renderProgress();
    }

    stopAutoplay() {
        if (this.mediaEndedBound) {
            this.element.removeEventListener('vanssa-slide-video:ended', this.mediaEndedBound);
            this.mediaEndedBound = null;
        }

        if (this.mediaPlayingBound) {
            this.element.removeEventListener('vanssa-slide-video:playing', this.mediaPlayingBound);
            this.mediaPlayingBound = null;
        }

        if (this.timer === null) {
            return;
        }

        window.clearTimeout(this.timer);
        this.timer = null;
        this.resetProgress();
    }

    // Visible auto-playing video/embed of the active slide, if any — the
    // media whose end should advance the slider.
    activeAutoplayMedia() {
        const slide = this.slideTargets[this.currentIndex] ?? null;
        if (!slide || slide.dataset.vanssaVideoPlayback === 'click') {
            return null;
        }

        return [...slide.querySelectorAll('video.vanssa-slide__media, iframe.vanssa-slide__media--embed')]
            .find((media) => window.getComputedStyle(media).display !== 'none') ?? null;
    }

    ownsActiveSlideEvent(event) {
        const slide = event.target instanceof Element ? event.target.closest('.vanssa-slide') : null;

        return slide !== null && slide === (this.slideTargets[this.currentIndex] ?? null);
    }

    renderProgress(durationMs = null) {
        if (!this.hasProgressBarTarget || (this.structural()?.showProgressBar ?? this.optionsValue?.showProgressBar) !== true) {
            return;
        }

        const duration = Number.isFinite(durationMs) && durationMs > 0 ? durationMs : this.autoplayInterval();
        const bar = this.progressBarTarget;
        bar.style.transition = 'none';
        bar.style.width = '0%';
        // Force a reflow so the width reset applies before the animation starts.
        void bar.offsetWidth;
        bar.style.transition = `width ${duration}ms linear`;
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

        const activeSlide = this.slideTargets[this.currentIndex] ?? null;
        const activeMedia = this.activeSlideMedia();
        if (!activeSlide || !activeMedia) {
            return;
        }

        const strength = this.parallaxStrengthFor(activeSlide);
        if (strength <= 0) {
            this.resetParallax();

            return;
        }

        const rect = this.parallaxWrapper.getBoundingClientRect();
        if (rect.width <= 0 || rect.height <= 0) {
            return;
        }

        const relativeX = (event.clientX - rect.left) / rect.width;
        const relativeY = (event.clientY - rect.top) / rect.height;
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
        const anySlideStrength = this.slideTargets.some((slide) => this.parallaxStrengthFor(slide) > 0);
        if (this.parallaxStrength() <= 0 && !anySlideStrength) {
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

    // Effective strength for one slide: its own data attribute wins over the
    // slider-level option; '0' explicitly disables parallax for that slide.
    parallaxStrengthFor(slideElement) {
        const raw = (slideElement?.dataset?.vanssaParallaxStrength ?? '').trim();
        if (raw === '') {
            return this.parallaxStrength();
        }

        const parsed = raw === '0' ? 0 : this.parseLengthToPx(raw);
        if (!Number.isFinite(parsed)) {
            return this.parallaxStrength();
        }

        return Math.max(0, Math.min(200, parsed));
    }

    // Read any --vanssa-* custom property from the slider root, so themes and
    // integrations can reach every admin-configured value from JS.
    cssVar(name) {
        return window.getComputedStyle(this.element).getPropertyValue(name).trim();
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
