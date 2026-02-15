import { startStimulusApp } from '@symfony/stimulus-bridge';
import ImageUploadPreviewController from './controllers/image_upload_preview_controller.js';
import RgbaColorPickerController from './controllers/rgba_color_picker_controller.js';
import SlidePreviewController from './controllers/slide_preview_controller.js';
import SliderSettingsController from './controllers/slider_settings_controller.js';
import SliderSlidesPreviewController from './controllers/slider_slides_preview_controller.js';
import './styles/slide_preview.scss';
import './styles/rgba_color_picker.scss';

const app = startStimulusApp();
app.register('vanssa-image-upload-preview', ImageUploadPreviewController);
app.register('vanssa-rgba-color-picker', RgbaColorPickerController);
app.register('vanssa-slide-preview', SlidePreviewController);
app.register('slider-settings', SliderSettingsController);
app.register('vanssa-slider-slides-preview', SliderSlidesPreviewController);
