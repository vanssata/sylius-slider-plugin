// Admin entry of the @vanssa/sylius-slider-plugin UX package.
//
// The plugin's Stimulus controllers (admin AND the shop slider/slide-video pair
// used by the turbo-frame previews) register through the stimulus-bridge
// manifest (package.json "symfony.controllers" + controllers.json) inside the
// APP's own startStimulusApp() — never here. Starting a second Stimulus
// application from this file makes every controller double-fire (see CLAUDE.md
// "Stimulus Controller Manifests").
//
// This entry only carries what must run eagerly outside Stimulus: the Turbo
// drive opt-out, the sidebar focus behavior, and the admin stylesheets.
import * as Turbo from '@hotwired/turbo';
import './styles/rgba_color_picker.scss';
import './styles/accordion.scss';
import './styles/preview_modal.scss';
import './styles/preview_panel.scss';
import './styles/slider_slides_preview.scss';

// Only Turbo Frames are wanted here (to swap the preview section without a
// classic iframe); Turbo Drive would intercept every link/form click across
// the whole Sylius admin, which isn't built with Turbo navigation in mind.
Turbo.session.drive = false;

// On Slider Management pages, keep OUR sidebar group open and collapse the
// other sections, so the section the admin is working in is always visible.
const focusSidebarOnSliderSection = () => {
    if (!/^\/admin\/(sliders|slides|style-presets)(\/|\?|$)/.test(window.location.pathname)) {
        return;
    }

    // CSS gate: Tabler renders sidebar dropdown menus statically visible, so
    // collapsing the other sections needs a stylesheet rule scoped to this
    // marker class (see preview_panel.scss).
    document.body.classList.add('vanssa-slider-section');

    document.querySelectorAll('aside .nav-item').forEach((item) => {
        const toggle = item.querySelector('a.nav-link.dropdown-toggle');
        const menu = item.querySelector('.dropdown-menu');
        if (!toggle || !menu) {
            return;
        }

        const isSliderSection = !!menu.querySelector('a[href^="/admin/sliders"], a[href^="/admin/slides"], a[href^="/admin/style-presets"]');
        toggle.classList.toggle('show', isSliderSection);
        toggle.setAttribute('aria-expanded', isSliderSection ? 'true' : 'false');
        menu.classList.toggle('show', isSliderSection);
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', focusSidebarOnSliderSection);
} else {
    focusSidebarOnSliderSection();
}
