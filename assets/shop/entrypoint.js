import { startStimulusApp } from '@symfony/stimulus-bridge';
import SliderController from './controllers/slider_controller.js';
import './styles/slider.css';

const app = startStimulusApp();
app.register('vanssa-slider', SliderController);
