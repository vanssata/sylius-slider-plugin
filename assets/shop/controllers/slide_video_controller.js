import { Controller } from '@hotwired/stimulus';

/**
 * Drives one slide video — a self-hosted <video> OR an external provider
 * <iframe> embed (YouTube, enablejsapi=1).
 *
 * - autoplay mode (default): plays only while the element is visible in the
 *   viewport (display:none breakpoint variants never intersect) AND its
 *   slide is active. Works standalone too (banner pages, no slide wrapper).
 * - click mode: never starts on its own — the slide's play-button overlay
 *   starts it; deactivating the slide pauses and brings the button back.
 *
 * Dispatches bubbling events consumed by the slider's autoplay gating:
 * - vanssa-slide-video:playing (detail.remainingMs when known)
 * - vanssa-slide-video:ended
 */
export default class extends Controller {
    static values = {
        playback: { type: String, default: 'autoplay' },
    };

    connect() {
        this.inView = false;
        this.userStarted = false;
        this.isEmbed = this.element.tagName === 'IFRAME';
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

        this.playButton = this.slide?.querySelector('.vanssa-slide__video-play') ?? null;
        if (this.playButton) {
            this.playButtonBound = () => this.requestPlay();
            this.playButton.addEventListener('click', this.playButtonBound);
        }

        if (this.isEmbed) {
            this.setupEmbedMessaging();
        } else {
            this.setupVideoEvents();
        }

        this.sync();
    }

    disconnect() {
        this.viewportObserver?.disconnect();
        this.viewportObserver = null;
        this.classObserver?.disconnect();
        this.classObserver = null;

        if (this.playButton && this.playButtonBound) {
            this.playButton.removeEventListener('click', this.playButtonBound);
        }

        if (this.messageBound) {
            window.removeEventListener('message', this.messageBound);
            this.messageBound = null;
        }

        if (this.embedLoadBound) {
            this.element.removeEventListener('load', this.embedLoadBound);
            this.embedLoadBound = null;
        }

        if (!this.isEmbed && !this.element.paused) {
            this.element.pause();
        }
    }

    clickMode() {
        return this.playbackValue === 'click';
    }

    sync() {
        const slideActive = this.slide === null || this.slide.classList.contains('is-active');

        if (this.clickMode()) {
            // Visitor-started only; deactivating the slide stops playback and
            // brings the play button back.
            if (!slideActive && this.userStarted) {
                this.userStarted = false;
                this.slide?.classList.remove('is-video-started');
                this.mediaPause();
            } else if (this.userStarted && !this.inView) {
                this.mediaPause();
            } else if (this.userStarted && this.inView && slideActive) {
                this.mediaPlay();
            }

            return;
        }

        if (this.inView && slideActive) {
            this.mediaPlay();
        } else {
            this.mediaPause();
        }
    }

    // The play button lives once per slide; every media variant listens, but
    // only the currently displayed one (its breakpoint) actually starts.
    requestPlay() {
        if (window.getComputedStyle(this.element).display === 'none') {
            return;
        }

        this.userStarted = true;
        this.slide?.classList.add('is-video-started');
        this.mediaPlay();
    }

    mediaPlay() {
        if (this.isEmbed) {
            this.postEmbedCommand('playVideo');

            return;
        }

        const playPromise = this.element.play();
        if (playPromise) {
            playPromise.catch(() => {});
        }
    }

    mediaPause() {
        if (this.isEmbed) {
            this.postEmbedCommand('pauseVideo');

            return;
        }

        if (!this.element.paused) {
            this.element.pause();
        }
    }

    setupVideoEvents() {
        this.element.addEventListener('playing', () => {
            const { duration, currentTime } = this.element;
            const remainingMs = Number.isFinite(duration) && duration > 0 ? Math.max(0, (duration - currentTime) * 1000) : null;
            this.dispatchMediaEvent('playing', { remainingMs });
        });

        this.element.addEventListener('ended', () => {
            this.handleEnded();
        });
    }

    // YouTube IFrame messaging: after the handshake the player posts state
    // updates (playerState 1 = playing, 0 = ended) we translate into the
    // same events the self-hosted videos dispatch.
    setupEmbedMessaging() {
        this.messageBound = (event) => {
            if (event.source !== this.element.contentWindow) {
                return;
            }

            let data = event.data;
            if (typeof data === 'string') {
                try {
                    data = JSON.parse(data);
                } catch {
                    return;
                }
            }

            const state = data?.info?.playerState;
            if (state === 1) {
                const duration = Number(data?.info?.duration);
                const currentTime = Number(data?.info?.currentTime);
                const remainingMs = Number.isFinite(duration) && duration > 0 && Number.isFinite(currentTime)
                    ? Math.max(0, (duration - currentTime) * 1000)
                    : null;
                this.dispatchMediaEvent('playing', { remainingMs });
            } else if (state === 0) {
                this.handleEnded();
            }
        };
        window.addEventListener('message', this.messageBound);

        this.embedLoadBound = () => this.postEmbedListening();
        this.element.addEventListener('load', this.embedLoadBound);
        this.postEmbedListening();
    }

    postEmbedListening() {
        this.element.contentWindow?.postMessage(JSON.stringify({ event: 'listening', id: this.element.id || 'vanssa-slide-video', channel: 'widget' }), '*');
    }

    postEmbedCommand(func) {
        this.element.contentWindow?.postMessage(JSON.stringify({ event: 'command', func, args: [] }), '*');
    }

    handleEnded() {
        if (this.clickMode()) {
            this.userStarted = false;
            this.slide?.classList.remove('is-video-started');
        }

        this.dispatchMediaEvent('ended');
    }

    dispatchMediaEvent(name, detail = {}) {
        this.element.dispatchEvent(new CustomEvent(`vanssa-slide-video:${name}`, { bubbles: true, detail }));
    }
}
