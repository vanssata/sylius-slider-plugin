import { startStimulusApp } from '@symfony/stimulus-bridge';
import SliderController from './controllers/slider_controller.js';
import SlideVideoController from './controllers/slide_video_controller.js';
import './styles/slider.scss';

const app = startStimulusApp();
app.register('vanssa-slider', SliderController);
app.register('vanssa-slide-video', SlideVideoController);
