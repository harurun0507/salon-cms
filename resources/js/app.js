import './bootstrap';
import './admin-workspace-cards';
import { initAllSlideCarousels, initSlideCarousel } from './public-slide-carousel';

window.SalonPublicSlideCarousel = {
    init: initSlideCarousel,
    initAll: initAllSlideCarousels,
};

document.addEventListener('DOMContentLoaded', () => {
    initAllSlideCarousels();
});
