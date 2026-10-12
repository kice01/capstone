{{--
    Reusable Barcode Scanner Component (Tailwind version)
--}}
<div id="scannerModal" class="hidden fixed inset-0 bg-black/70 z-50 items-center justify-center p-4">
    <div class="bg-white rounded-lg p-4 max-w-md w-full">
        <div class="flex justify-between items-center mb-3">
            <h3 class="font-semibold">📸 Point camera at barcode</h3>
            <button type="button" onclick="closeScanner()" class="text-gray-500 text-xl">✕</button>
        </div>
        <div id="scanner-reader" style="width: 100%; min-height: 220px; background: #111827; border-radius: 0.5rem; overflow: hidden;"></div>
        <p id="scanner-status" class="text-sm text-gray-600 mt-2 text-center">Starting camera. Allow camera access if your browser asks.</p>
        <p id="scanner-error" class="hidden text-sm text-red-800 bg-red-50 rounded p-3 mt-3"></p>
        <button id="scanner-retry" type="button" onclick="startScanner()" class="hidden mt-3 w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">Try Camera Again</button>
    </div>
</div>
<script src="{{ asset('js/html5-qrcode.min.js') }}"></script>
<script>
    let html5QrCode = null;
    let scannerCallback = null;
    let scannerStarting = false;

    const BARCODE_FORMATS = [
        Html5QrcodeSupportedFormats.EAN_13,
        Html5QrcodeSupportedFormats.EAN_8,
        Html5QrcodeSupportedFormats.UPC_A,
        Html5QrcodeSupportedFormats.UPC_E,
        Html5QrcodeSupportedFormats.CODE_128,
        Html5QrcodeSupportedFormats.CODE_39,
        Html5QrcodeSupportedFormats.CODE_93,
        Html5QrcodeSupportedFormats.ITF,
    ];

    function openScanner(callbackFunctionName) {
        if (!window.isSecureContext || !navigator.mediaDevices?.getUserMedia) {
            const secureUrl = new URL(window.location.href);
            secureUrl.protocol = 'https:';
            if (secureUrl.port === '8000') secureUrl.port = '8443';

            if (window.location.protocol !== 'https:' || window.location.port === '8000') {
                window.location.replace(secureUrl.toString());
                return;
            }

            const modal = document.getElementById('scannerModal');
            const error = document.getElementById('scanner-error');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            error.textContent = 'Camera access needs a trusted HTTPS connection. Accept or install the site certificate on this phone, then reload this page.';
            error.classList.remove('hidden');
            return;
        }

        scannerCallback = callbackFunctionName;
        const modal = document.getElementById('scannerModal');
        document.getElementById('scanner-error').classList.add('hidden');
        document.getElementById('scanner-retry').classList.add('hidden');
        document.getElementById('scanner-status').textContent = 'Starting camera. Allow camera access if your browser asks.';
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        // Give the browser time to reflow/layout the now-visible modal
        // before html5-qrcode measures the container size.
        setTimeout(startScanner, 200);
    }

    async function startScanner() {
        if (scannerStarting) return;
        scannerStarting = true;
        const status = document.getElementById('scanner-status');
        const errorMessage = document.getElementById('scanner-error');
        const retryButton = document.getElementById('scanner-retry');
        errorMessage.classList.add('hidden');
        retryButton.classList.add('hidden');
        status.textContent = 'Requesting camera access...';

        try {
            if (!window.isSecureContext || !navigator.mediaDevices?.getUserMedia) {
                throw new Error('This page needs a trusted HTTPS connection to use the camera.');
            }

            if (typeof Html5Qrcode === 'undefined') {
                throw new Error('The barcode scanner did not load. Reload the page and try again.');
            }

            html5QrCode = new Html5Qrcode('scanner-reader', {
                formatsToSupport: BARCODE_FORMATS,
                verbose: false
            });

            await html5QrCode.start(
                { facingMode: 'environment' },
                {
                    fps: 10,
                    qrbox: (viewfinderWidth, viewfinderHeight) => ({
                        width: Math.min(280, Math.max(180, viewfinderWidth - 32)),
                        height: Math.min(180, Math.max(120, viewfinderHeight - 32))
                    }),
                    disableFlip: false
                },
                (decodedText) => {
                    const callbackName = scannerCallback;
                    closeScanner();
                    if (callbackName && typeof window[callbackName] === 'function') {
                        window[callbackName](decodedText);
                    }
                },
                () => {
                    // Called repeatedly while no barcode is detected; ignore.
                }
            );

            status.textContent = 'Camera is on. Point it at the barcode and hold steady.';
        } catch (err) {
            const details = err?.name === 'NotAllowedError' || err?.name === 'PermissionDeniedError'
                ? 'Allow camera permission for this site in your browser settings, then try again.'
                : err?.name === 'NotFoundError'
                    ? 'No camera was found on this device.'
                    : err?.name === 'NotReadableError'
                        ? 'The camera is being used by another app. Close that app and try again.'
                        : (err?.message || 'Unknown camera error.');

            status.textContent = 'Camera could not be started.';
            errorMessage.textContent = details;
            errorMessage.classList.remove('hidden');
            retryButton.classList.remove('hidden');

            if (html5QrCode) {
                try { await html5QrCode.clear(); } catch (_) {}
                html5QrCode = null;
            }
        } finally {
            scannerStarting = false;
        }
    }

    function closeScanner() {
        stopScanner();
        const modal = document.getElementById('scannerModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function stopScanner() {
        if (html5QrCode) {
            const scanner = html5QrCode;
            html5QrCode = null;
            scanner.stop().then(() => scanner.clear()).catch(() => {});
        }
    }
</script>
