import { startStimulusApp } from '@symfony/stimulus-bridge';
import RgbaColorPickerController from './controllers/rgba_color_picker_controller';
import SlidePreviewController from './controllers/slide_preview_controller';
import SliderSettingsController from './controllers/slider_settings_controller';
import SliderSlidesPreviewController from './controllers/slider_slides_preview_controller';
import './styles/slide_preview.css';
import './styles/rgba_color_picker.css';

const app = startStimulusApp();
app.register('vanssa-rgba-color-picker', RgbaColorPickerController);
app.register('vanssa-slide-preview', SlidePreviewController);
app.register('slider-settings', SliderSettingsController);
app.register('vanssa-slider-slides-preview', SliderSlidesPreviewController);
