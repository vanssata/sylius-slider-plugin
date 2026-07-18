import { startStimulusApp } from '@symfony/stimulus-bridge';
import * as Turbo from '@hotwired/turbo';
import AnimationSettingsController from './controllers/animation_settings_controller.js';
import FormContextController from './controllers/form_context_controller.js';
import ImageUploadPreviewController from './controllers/image_upload_preview_controller.js';
import MockupPickerController from './controllers/mockup_picker_controller.js';
import ModalPortalController from './controllers/modal_portal_controller.js';
import PresetApplierController from './controllers/preset_applier_controller.js';
import PresetGalleryController from './controllers/preset_gallery_controller.js';
import PreviewFrameController from './controllers/preview_frame_controller.js';
import ResponsiveCopyController from './controllers/responsive_copy_controller.js';
import RgbaColorPickerController from './controllers/rgba_color_picker_controller.js';
import SliderSettingsController from './controllers/slider_settings_controller.js';
import SliderSlidesPreviewController from './controllers/slider_slides_preview_controller.js';
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

const app = startStimulusApp();
app.register('vanssa-animation-settings', AnimationSettingsController);
app.register('vanssa-form-context', FormContextController);
app.register('vanssa-image-upload-preview', ImageUploadPreviewController);
app.register('vanssa-mockup-picker', MockupPickerController);
app.register('vanssa-modal-portal', ModalPortalController);
app.register('vanssa-preset-applier', PresetApplierController);
app.register('vanssa-preset-gallery', PresetGalleryController);
app.register('vanssa-responsive-copy', ResponsiveCopyController);
app.register('vanssa-preview-frame', PreviewFrameController);
app.register('vanssa-rgba-color-picker', RgbaColorPickerController);
app.register('slider-settings', SliderSettingsController);
app.register('vanssa-slider-slides-preview', SliderSlidesPreviewController);
