<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                New Sale
            </h2>
            <a href="{{ route('sales.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded">
                Back
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="bg-red-100 text-red-700 p-4 rounded mb-4">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Scan/search + Cart side by side --}}
            <div class="grid grid-cols-1 md:grid-cols-[280px_1fr] gap-3 mb-4 items-start">
                {{-- Barcode Scan Box --}}
                <div class="bg-white shadow rounded-lg p-5 border-2 border-blue-400">
                    <label class="block mb-2 font-semibold text-gray-700 text-sm">📷 Scan Barcode</label>
                    <div class="flex flex-col gap-2">
                        <input type="text" id="barcode-input" autofocus autocomplete="off"
                               placeholder="Scan or type barcode..."
                               class="border-2 border-blue-300 rounded w-full p-2.5 text-sm focus:border-blue-500 focus:outline-none">
                        <button type="button" id="camera-scan-btn" class="bg-blue-600 text-white px-4 py-2 rounded text-sm whitespace-nowrap">
                            📸 Use Camera
                        </button>
                    </div>
                    <p id="scan-feedback" class="text-sm mt-2 h-5"></p>

                    {{-- Manual add fallback --}}
                    <div class="relative mt-3 pt-3 border-t">
                        <label class="block mb-2 text-sm text-gray-600">Or search a product manually</label>
                        <input type="text" id="manual-search" autocomplete="off"
                               placeholder="Type product name..."
                               class="border rounded w-full p-2.5 text-sm">
                        <div id="manual-dropdown"
                             class="hidden absolute z-20 bg-white border rounded w-full max-h-72 overflow-y-auto shadow-lg mt-1">
                        </div>
                    </div>
                </div>

                {{-- Cart --}}
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="font-semibold mb-3">🛒 Customer's Cart</h3>

                    <table class="w-full text-sm border-collapse">
                        <thead>
                            <tr class="text-left text-gray-500 border-b">
                                <th class="py-2">Product</th>
                                <th class="py-2 text-center">Qty</th>
                                <th class="py-2 text-right">Price</th>
                                <th class="py-2 text-right">Subtotal</th>
                                <th class="py-2"></th>
                            </tr>
                        </thead>
                        <tbody id="cart-body">
                            <tr id="empty-cart-row">
                                <td colspan="5" class="text-center text-gray-400 py-8">Cart is empty. Scan or add a product to start.</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="text-right font-bold text-xl mt-4 pt-4 border-t">
                        Grand Total: ₱<span id="total-display">0.00</span>
                    </div>
                </div>
            </div>

            {{-- Sale details + submit --}}
            <div class="bg-white shadow rounded-lg p-6">
                <form action="{{ route('sales.store') }}" method="POST" id="sale-form">
                    @csrf

                    <div class="mb-4">
                        <label class="block mb-2">Customer Name (optional)</label>
                        <input type="text" name="customer_name" class="border rounded w-full p-2" value="{{ old('customer_name') }}">
                    </div>

                    <div class="mb-4">
                        <label class="block mb-2">Sale Date</label>
                        <input type="date" name="sale_date" class="border rounded w-full p-2" value="{{ old('sale_date', date('Y-m-d')) }}" required>
                    </div>

                    <div class="mb-4">
                        <label class="block mb-2">Payment Method</label>
                        <select name="payment_method" id="payment-method" class="border rounded w-full p-2" required>
                            <option value="Cash">Cash</option>
                            <option value="GCash">GCash</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block mb-2" id="received-label">Cash Tendered</label>
                        <input type="number" step="0.01" min="0" name="cash_tendered" id="cash-tendered"
                               class="border rounded w-full p-2" placeholder="0.00" required>
                        <p class="text-sm mt-1" id="change-display">Change: ₱0.00</p>
                    </div>

                    {{-- Hidden inputs injected by JS on submit --}}
                    <div id="hidden-inputs"></div>

                    <button type="submit" id="submit-btn" class="bg-blue-600 text-white px-4 py-2 rounded" disabled>
                        Save Sale
                    </button>
                    <a href="{{ route('sales.index') }}" class="ml-3 text-gray-600">Cancel</a>
                </form>
            </div>

        </div>
    </div>

    <script>
        const barcodeInput = document.getElementById('barcode-input');
        const scanFeedback = document.getElementById('scan-feedback');
        const cartBody = document.getElementById('cart-body');
        const emptyCartRow = document.getElementById('empty-cart-row');
        const totalDisplay = document.getElementById('total-display');
        const hiddenInputs = document.getElementById('hidden-inputs');
        const submitBtn = document.getElementById('submit-btn');
        const manualSearch = document.getElementById('manual-search');
        const manualDropdown = document.getElementById('manual-dropdown');

        const productList = [
            @foreach($products as $product)
                {
                    id: {{ $product->id }},
                    name: @json(trim($product->name . ($product->size ? ' ' . $product->size : '') . ($product->unit ? ' ' . $product->unit : ''))),
                    category: @json($product->category?->name ?? ''),
                    barcode: @json($product->barcode ?? ''),
                    price: {{ $product->price }},
                    stock: {{ $product->sellable_quantity }},
                    expiry: @json($product->batches->first()?->expiration_date)
                },
            @endforeach
        ];

        const cashTenderedInput = document.getElementById('cash-tendered');
        const changeDisplay = document.getElementById('change-display');
        const paymentMethod = document.getElementById('payment-method');
        const receivedLabel = document.getElementById('received-label');

        let currentTotal = 0;

        function updateChange() {
            if (paymentMethod.value === 'GCash') {
                cashTenderedInput.value = currentTotal.toFixed(2);
                cashTenderedInput.disabled = true;
                cashTenderedInput.required = false;
                receivedLabel.textContent = 'GCash Amount';
                changeDisplay.textContent = 'Digital payment: ₱' + currentTotal.toFixed(2);
                changeDisplay.className = 'text-sm mt-1 text-green-600 font-medium';
                return;
            }

            cashTenderedInput.disabled = false;
            cashTenderedInput.required = true;
            receivedLabel.textContent = 'Cash Tendered';
            const cash = parseFloat(cashTenderedInput.value) || 0;
            const change = cash - currentTotal;
            if (cash === 0) {
                changeDisplay.textContent = 'Change: ₱0.00';
                changeDisplay.className = 'text-sm mt-1 text-gray-500';
            } else if (change < 0) {
                changeDisplay.textContent = 'Insufficient cash (short by ₱' + Math.abs(change).toFixed(2) + ')';
                changeDisplay.className = 'text-sm mt-1 text-red-600 font-medium';
            } else {
                changeDisplay.textContent = 'Change: ₱' + change.toFixed(2);
                changeDisplay.className = 'text-sm mt-1 text-green-600 font-medium';
            }
        }

        cashTenderedInput.addEventListener('input', updateChange);
        paymentMethod.addEventListener('change', updateChange);

        // cart = { productId: { name, price, stock, qty } }
        let cart = {};

        function showFeedback(message, isError) {
            scanFeedback.textContent = message;
            scanFeedback.className = 'text-sm mt-2 h-5 font-medium ' + (isError ? 'text-red-600' : 'text-green-600');
            setTimeout(() => { scanFeedback.textContent = ''; }, 2500);
        }

        function isExpired(expiry) {
            if (!expiry) return false;
            const expiryDate = new Date(expiry);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            return expiryDate < today;
        }

        function renderCart() {
            const ids = Object.keys(cart);
            cartBody.innerHTML = '';

            if (ids.length === 0) {
                cartBody.appendChild(emptyCartRow);
                totalDisplay.textContent = '0.00';
                submitBtn.disabled = true;
                hiddenInputs.innerHTML = '';
                currentTotal = 0;
                updateChange();
                return;
            }

            let total = 0;
            let hiddenHtml = '';

            ids.forEach(id => {
                const item = cart[id];
                const subtotal = item.price * item.qty;
                total += subtotal;

                const row = document.createElement('tr');
                row.className = 'border-b';
                row.innerHTML = `
                    <td class="py-2">${item.name}</td>
                    <td class="py-2 text-center">
                        <div class="flex items-center justify-center gap-1">
                            <button type="button" class="qty-minus bg-gray-200 w-6 h-6 rounded" data-id="${id}">−</button>
                            <span class="w-8 text-center">${item.qty}</span>
                            <button type="button" class="qty-plus bg-gray-200 w-6 h-6 rounded" data-id="${id}">+</button>
                        </div>
                    </td>
                    <td class="py-2 text-right">₱${item.price.toFixed(2)}</td>
                    <td class="py-2 text-right font-medium">₱${subtotal.toFixed(2)}</td>
                    <td class="py-2 text-right">
                        <button type="button" class="remove-item text-red-600" data-id="${id}">✕</button>
                    </td>
                `;
                cartBody.appendChild(row);

                hiddenHtml += `<input type="hidden" name="product_id[]" value="${id}">`;
                hiddenHtml += `<input type="hidden" name="quantity[]" value="${item.qty}">`;
            });

            totalDisplay.textContent = total.toFixed(2);
            hiddenInputs.innerHTML = hiddenHtml;
            submitBtn.disabled = false;
            currentTotal = total;
            updateChange();
        }

        function addToCart(id, name, price, stock, expiry) {
            id = String(id);
            price = parseFloat(price);
            stock = parseInt(stock);

            if (isExpired(expiry)) {
                showFeedback('❌ ' + name + ' has expired and cannot be sold.', true);
                return;
            }

            if (!cart[id]) {
                if (stock < 1) {
                    showFeedback('⚠️ Out of stock: ' + name, true);
                    return;
                }
                cart[id] = { name, price, stock, qty: 1 };
                showFeedback('✅ Added: ' + name, false);
            } else {
                if (cart[id].qty + 1 > stock) {
                    showFeedback('⚠️ Not enough stock for ' + name + ' (available: ' + stock + ')', true);
                    return;
                }
                cart[id].qty += 1;
                showFeedback('✅ +1 ' + name + ' (qty: ' + cart[id].qty + ')', false);
            }
            renderCart();
        }

        function handleBarcode(code) {
            code = code.trim();
            if (!code) return;

            const matches = window.barcodeMap?.[code] || [];
            if (matches.length === 1) {
                const p = matches[0];
                addToCart(p.id, p.name, p.price, p.stock, p.expiry);
                return;
            }
            if (matches.length > 1) {
                showFeedback('This barcode matches multiple products. Search and select the right size manually.', true);
                manualSearch.value = code;
                renderManualDropdown(code);
                manualSearch.focus();
                return;
            }

            showFeedback('❌ No product found for barcode: ' + code, true);
        }

        // Keep duplicate codes visible: a barcode collision must not silently sell the wrong variant.
        window.barcodeMap = {};
            @foreach($products as $product)
                @if($product->barcode)
                (window.barcodeMap[@json($product->barcode)] ||= []).push({
                    id: {{ $product->id }},
                    name: @json(trim($product->name . ($product->size ? ' ' . $product->size : '') . ($product->unit ? ' ' . $product->unit : ''))),
                    price: {{ $product->price }},
                    stock: {{ $product->sellable_quantity }},
                    expiry: @json($product->batches->first()?->expiration_date)
                });
                @endif
            @endforeach

        barcodeInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                handleBarcode(barcodeInput.value);
                barcodeInput.value = '';
                barcodeInput.focus();
            }
        });

        function renderManualDropdown(query) {
            const q = query.trim().toLowerCase();
            const matches = q.length === 0
                ? productList
                : productList.filter(p => p.name.toLowerCase().includes(q) || p.category.toLowerCase().includes(q) || p.barcode.includes(q) || String(p.id).includes(q));

            if (matches.length === 0) {
                manualDropdown.innerHTML = '<div class="p-3 text-gray-400 text-sm">No products found.</div>';
                manualDropdown.classList.remove('hidden');
                return;
            }

            manualDropdown.innerHTML = matches.slice(0, 20).map(p => {
                const expired = isExpired(p.expiry);
                const outOfStock = p.stock < 1;
                const disabled = expired || outOfStock;
                const statusLabel = expired
                    ? '<span class="text-red-600 text-xs font-medium">EXPIRED</span>'
                    : outOfStock
                        ? '<span class="text-red-600 text-xs font-medium">OUT OF STOCK</span>'
                        : `<span class="text-gray-400 text-xs">stock: ${p.stock}</span>`;

                return `
                    <div class="manual-result flex justify-between items-center p-3 border-b last:border-b-0 ${disabled ? 'opacity-50' : 'cursor-pointer hover:bg-gray-50'}"
                         data-id="${p.id}" data-disabled="${disabled ? '1' : '0'}">
                        <div>
                            <p class="font-medium">${p.name}</p>
                            ${statusLabel}
                        </div>
                        <p class="font-semibold">₱${p.price.toFixed(2)}</p>
                    </div>
                `;
            }).join('');

            manualDropdown.classList.remove('hidden');
        }

        manualSearch.addEventListener('focus', function () {
            renderManualDropdown(manualSearch.value);
        });

        manualSearch.addEventListener('input', function () {
            renderManualDropdown(manualSearch.value);
        });

        manualDropdown.addEventListener('click', function (e) {
            const row = e.target.closest('.manual-result');
            if (!row) return;
            if (row.dataset.disabled === '1') return;

            const p = productList.find(prod => String(prod.id) === row.dataset.id);
            if (!p) return;

            addToCart(p.id, p.name, p.price, p.stock, p.expiry);
            manualSearch.value = '';
            manualDropdown.classList.add('hidden');
        });

        document.addEventListener('click', function (e) {
            if (!manualSearch.contains(e.target) && !manualDropdown.contains(e.target)) {
                manualDropdown.classList.add('hidden');
            }
        });

        cartBody.addEventListener('click', function (e) {
            const id = e.target.dataset.id;
            if (!id) return;

            if (e.target.classList.contains('qty-plus')) {
                if (cart[id].qty + 1 > cart[id].stock) {
                    showFeedback('⚠️ Not enough stock (available: ' + cart[id].stock + ')', true);
                    return;
                }
                cart[id].qty += 1;
                renderCart();
            } else if (e.target.classList.contains('qty-minus')) {
                cart[id].qty -= 1;
                if (cart[id].qty <= 0) delete cart[id];
                renderCart();
            } else if (e.target.classList.contains('remove-item')) {
                delete cart[id];
                renderCart();
            }
        });

        barcodeInput.focus();
    </script>

    {{-- Camera Scanner Modal --}}
    <div id="camera-modal" class="hidden fixed inset-0 bg-black/70 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg p-4 max-w-md w-full">
            <div class="flex justify-between items-center mb-3">
                <h3 class="font-semibold">📸 Point camera at barcode</h3>
                <button type="button" id="close-camera-btn" class="text-gray-500 text-xl">✕</button>
            </div>
            <div id="camera-reader" style="width: 100%;"></div>
            <p class="text-xs text-gray-400 mt-2 text-center">Hold steady, good lighting for best results.</p>
        </div>
    </div>

    <script>
        (function () {
            const cameraBtn = document.getElementById('camera-scan-btn');
            const closeBtn = document.getElementById('close-camera-btn');
            const modal = document.getElementById('camera-modal');
            let html5QrCode = null;

            cameraBtn.addEventListener('click', async function () {
                modal.classList.remove('hidden');

                html5QrCode = new Html5Qrcode('camera-reader');
                const Html5QrcodeSupportedFormats = window.Html5QrcodeSupportedFormats;
                try {
                    await html5QrCode.start(
                        { facingMode: 'environment' },
                        {
                            fps: 10,
                            qrbox: { width: 280, height: 150 },
                            formatsToSupport: [
                                Html5QrcodeSupportedFormats.EAN_13,
                                Html5QrcodeSupportedFormats.EAN_8,
                                Html5QrcodeSupportedFormats.UPC_A,
                                Html5QrcodeSupportedFormats.UPC_E,
                                Html5QrcodeSupportedFormats.CODE_128,
                                Html5QrcodeSupportedFormats.CODE_39,
                                Html5QrcodeSupportedFormats.QR_CODE
                            ]
                        },
                        function (decodedText) {
                            handleBarcode(decodedText);
                            stopCamera();
                        },
                        function () { /* ignore per-frame scan errors */ }
                    );
                } catch (err) {
                    showFeedback('❌ Cannot access camera: ' + err, true);
                    modal.classList.add('hidden');
                }
            });

            function stopCamera() {
                if (html5QrCode) {
                    html5QrCode.stop().then(() => {
                        html5QrCode.clear();
                        modal.classList.add('hidden');
                    }).catch(() => {
                        modal.classList.add('hidden');
                    });
                }
            }

            closeBtn.addEventListener('click', stopCamera);
        })();
    </script>
</x-app-layout>
