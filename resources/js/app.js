import Alpine from 'alpinejs';
import { Chart, registerables } from 'chart.js';
import { Html5Qrcode, Html5QrcodeSupportedFormats } from 'html5-qrcode';
import JsBarcode from 'jsbarcode';
import './search-suggestions';

const eyeIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>';
const eyeOffIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 3 18 18M10.6 10.6a2 2 0 0 0 2.8 2.8"/><path d="M9.9 5.2A10.8 10.8 0 0 1 12 5c6.4 0 10 7 10 7a15 15 0 0 1-3.1 3.9M6.2 6.2C3.5 8.1 2 12 2 12s3.6 7 10 7a10 10 0 0 0 4-.8"/></svg>';

function addPasswordVisibilityControls(root = document) {
    root.querySelectorAll('input[type="password"]:not([data-visibility-control])').forEach((input) => {
        input.dataset.visibilityControl = 'true';
        input.style.paddingRight = '2.75rem';

        const wrapper = document.createElement('span');
        wrapper.style.position = 'relative';
        wrapper.style.display = 'block';
        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'password-visibility-toggle';
        button.setAttribute('aria-label', 'Show password');
        button.setAttribute('aria-pressed', 'false');
        button.style.cssText = 'position:absolute;right:.75rem;top:50%;transform:translateY(-50%);display:flex;align-items:center;justify-content:center;width:1.5rem;height:1.5rem;padding:0;border:0;background:transparent;color:#6b7280;cursor:pointer;';
        button.innerHTML = eyeIcon;
        button.querySelector('svg').style.cssText = 'width:1.25rem;height:1.25rem;';

        button.addEventListener('click', () => {
            const showPassword = input.type === 'password';
            input.type = showPassword ? 'text' : 'password';
            button.setAttribute('aria-label', showPassword ? 'Hide password' : 'Show password');
            button.setAttribute('aria-pressed', String(showPassword));
            button.innerHTML = showPassword ? eyeOffIcon : eyeIcon;
            button.querySelector('svg').style.cssText = 'width:1.25rem;height:1.25rem;';
            input.focus();
        });

        wrapper.appendChild(button);
    });
}

addPasswordVisibilityControls();
new MutationObserver((mutations) => {
    mutations.forEach((mutation) => mutation.addedNodes.forEach((node) => {
        if (node.nodeType !== Node.ELEMENT_NODE) return;
        if (node.matches('input[type="password"]')) addPasswordVisibilityControls(node.parentNode);
        else addPasswordVisibilityControls(node);
    }));
}).observe(document.documentElement, { childList: true, subtree: true });

Chart.register(...registerables);
window.Chart = Chart;
window.Html5Qrcode = Html5Qrcode;
window.Html5QrcodeSupportedFormats = Html5QrcodeSupportedFormats;
window.JsBarcode = JsBarcode;
window.Alpine = Alpine;
Alpine.start();
