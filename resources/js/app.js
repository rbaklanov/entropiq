import './bootstrap';
import ApexCharts from 'apexcharts';
import { moneyToggle, themeColor, prefersReducedMotion } from './ink';

window.ApexCharts = ApexCharts;
window.themeColor = themeColor;
window.prefersReducedMotion = prefersReducedMotion;

document.addEventListener('alpine:init', () => {
    window.Alpine.data('moneyToggle', moneyToggle);
});
