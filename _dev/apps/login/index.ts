import Splide from '@splidejs/splide';
import "./assets/index.css"
import '@splidejs/splide/css/core';

document.addEventListener( 'DOMContentLoaded', () => {
  const slider = new Splide('#psacc_slider', {
    type: 'loop',
    autoplay: true,
  });
  slider.mount();
})
