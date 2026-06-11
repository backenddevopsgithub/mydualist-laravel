import './bootstrap';
import Alpine from 'alpinejs';
import 'flatpickr/dist/flatpickr.min.css';
import { initOnboardingDatePickers } from './onboarding-dates';

window.Alpine = Alpine;

document.addEventListener('DOMContentLoaded', () => {
    initOnboardingDatePickers();
    const revealElements = document.querySelectorAll('.reveal-on-scroll, .feature-fade');

    if (! revealElements.length) {
        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.12, rootMargin: '0px 0px -60px 0px' },
    );

    revealElements.forEach((element) => observer.observe(element));
});

Alpine.start();
