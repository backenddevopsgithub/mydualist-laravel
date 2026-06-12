import './bootstrap';
import Alpine from 'alpinejs';
import 'flatpickr/dist/flatpickr.min.css';
import { initOnboardingDatePickers } from './onboarding-dates';

window.Alpine = Alpine;

window.copyToClipboard = async (text) => {
    if (navigator.clipboard && window.isSecureContext) {
        try {
            await navigator.clipboard.writeText(text);

            return true;
        } catch (error) {
            // Fall back for browsers that expose Clipboard API but deny permission.
        }
    }

    const activeElement = document.activeElement;
    const scrollX = window.scrollX;
    const scrollY = window.scrollY;
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.setAttribute('readonly', '');
    textarea.style.position = 'fixed';
    textarea.style.top = '0';
    textarea.style.left = '0';
    textarea.style.width = '1px';
    textarea.style.height = '1px';
    textarea.style.opacity = '0';
    textarea.style.pointerEvents = 'none';
    document.body.appendChild(textarea);

    try {
        textarea.select();
        textarea.setSelectionRange(0, textarea.value.length);

        return document.execCommand('copy');
    } finally {
        document.body.removeChild(textarea);

        if (activeElement instanceof HTMLElement) {
            activeElement.focus({ preventScroll: true });
        }

        window.scrollTo(scrollX, scrollY);
    }
};

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
