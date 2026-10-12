<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Add Inventory
            </h2>
            <a href="{{ route('inventories.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded">
                Back
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">

            @if ($errors->any())
                <div class="bg-red-100 text-red-700 p-4 rounded mb-4">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Barcode Scan Box --}}
            <div class="bg-white shadow rounded-lg p-6 mb-4 border-2 border-blue-400">
                <label class="block mb-2 font-semibold text-gray-700">📷 Scan Product Barcode</label>
                <div class="flex gap-2">
                    <input type="text" id="barcode-input" autofocus autocomplete="off"
                           placeholder="Scan barcode to auto-select product..."
                           class="border-2 border-blue-300 rounded w-full p-3 text-lg focus:border-blue-500 focus:outline-none">
                    <button type="button" onclick="openScanner('onInventoryBarcodeScanned')"
                            class="bg-blue-600 text-white px-4 rounded whitespace-nowrap">
                        📸 Use Camera
                    </button>
                </div>
                <p id="scan-feedback" class="text-sm mt-2 h-5"></p>
            </div>
            <div class="bg-white shadow rounded-lg p-6">
                <form action="{{ route('inventories.store') }}" method="POST" id="inv-form">
                    @csrf

                    <div class="mb-4">
                        <label class="block mb-2">Product</label>
                        <select name="product_id" id="product-select" class="border rounded w-full p-2" required>
                            <option value="" disabled selected>Select a product or scan barcode above</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}"
                                    data-stock="{{ $product->quantity }}"
                                    data-barcode="{{ $product->barcode }}"
                                    {{ old('product_id') == $product->id ? 'selected' : '' }}>
                                    {{ $product->display_name }} (Current qty: {{ $product->quantity }})
                                </option>
                            @endforeach
                        </select>
                        <p class="text-sm text-gray-500 mt-1">
                            Current stock: <span id="current-stock-display" class="font-semibold">—</span>
                        </p>
                    </div>

                    <div class="mb-4">
                        <label class="block mb-2">Supplier</label>
                        <select name="supplier_id" class="border rounded w-full p-2">
                            <option value="">Select a supplier (required for stock-in)</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}"
                                    {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-1">
                        <div>
                            <label class="block mb-2">Stock In <span class="text-gray-400 text-sm">(incoming)</span></label>
                            <input type="number" name="stock_in" id="stock-in" min="0" class="border rounded w-full p-2" value="{{ old('stock_in', 0) }}" required>
                        </div>
                        <div>
                            <label class="block mb-2">Stock Out <span class="text-gray-400 text-sm">(outgoing/spoiled)</span></label>
                            <input type="number" name="stock_out" id="stock-out" min="0" class="border rounded w-full p-2" value="{{ old('stock_out', 0) }}">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="block mb-2">Purchase Cost per Unit <span class="text-gray-400 text-sm">(required for stock-in)</span></label>
                        <input type="number" name="unit_cost" min="0" step="0.01" class="border rounded w-full p-2" value="{{ old('unit_cost') }}">
                    </div>
                    <p id="validation-msg" class="text-sm text-red-600 mb-4 h-5"></p>

                    <div class="mb-4">
                        <label class="block mb-2">Reason / Reference</label>
                        <input type="text" name="reason" maxlength="255" class="border rounded w-full p-2" value="{{ old('reason') }}" placeholder="e.g. New delivery, spoiled items, stock correction" required>
                    </div>

                    <p class="text-sm text-gray-600 mb-4 bg-gray-50 border rounded p-2">
                        Resulting stock: <span id="resulting-stock" class="font-semibold">—</span>
                    </p>

                    <div class="mb-4">
                        <label class="block mb-2">Expiry Date <span class="text-gray-400 text-sm">(optional)</span></label>
                        <input type="date" name="expiry_date" class="border rounded w-full p-2" value="{{ old('expiry_date') }}">
                    </div>

                    <button type="submit" id="submit-btn" class="bg-blue-600 text-white px-4 py-2 rounded">
                        Save Inventory
                    </button>
                    <a href="{{ route('inventories.index') }}" class="ml-3 text-gray-600">
                        Cancel
                    </a>
                </form>
            </div>
        </div>
    </div>

    <script>
        const barcodeInput = document.getElementById('barcode-input');
        const scanFeedback = document.getElementById('scan-feedback');
        const productSelect = document.getElementById('product-select');
        const currentStockDisplay = document.getElementById('current-stock-display');
        const resultingStockDisplay = document.getElementById('resulting-stock');
        const stockIn = document.getElementById('stock-in');
        const stockOut = document.getElementById('stock-out');
        const validationMsg = document.getElementById('validation-msg');
        const submitBtn = document.getElementById('submit-btn');

        function currentStock() {
            const opt = productSelect.options[productSelect.selectedIndex];
            return opt && opt.dataset.stock !== undefined ? parseInt(opt.dataset.stock) : null;
        }

        function updateStockPreview() {
            const stock = currentStock();
            if (stock === null) {
                currentStockDisplay.textContent = '—';
                resultingStockDisplay.textContent = '—';
                return;
            }
            currentStockDisplay.textContent = stock;

            const inQty = parseInt(stockIn.value || 0);
            const outQty = parseInt(stockOut.value || 0);
            const result = stock + inQty - outQty;

            resultingStockDisplay.textContent = result;

            if (outQty > stock + inQty) {
                validationMsg.textContent = `⚠️ Stock Out exceeds available stock (${stock + inQty}).`;
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
            } else {
                validationMsg.textContent = '';
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        }

        productSelect.addEventListener('change', updateStockPreview);
        stockIn.addEventListener('input', updateStockPreview);
        stockOut.addEventListener('input', updateStockPreview);

        function showFeedback(message, isError) {
            scanFeedback.textContent = message;
            scanFeedback.className = 'text-sm mt-2 h-5 font-medium ' + (isError ? 'text-red-600' : 'text-green-600');
            setTimeout(() => { scanFeedback.textContent = ''; }, 2000);
        }

        barcodeInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const code = barcodeInput.value.trim();
                barcodeInput.value = '';
                if (!code) return;
                matchAndSelectByBarcode(code);
                barcodeInput.focus();
            }
        });
        function matchAndSelectByBarcode(code) {
            const matches = [];
            productSelect.querySelectorAll('option[data-barcode]').forEach(opt => {
                if (opt.dataset.barcode === code) matches.push(opt);
            });

            if (matches.length === 1) {
                const matched = matches[0];
                productSelect.value = matched.value;
                updateStockPreview();
                showFeedback('✅ Selected: ' + matched.textContent.trim(), false);
                stockIn.focus();
                stockIn.select();
            } else if (matches.length > 1) {
                showFeedback('This barcode belongs to multiple products. Select the correct product and size from the list.', true);
                productSelect.focus();
            } else {
                showFeedback('❌ No product found for barcode: ' + code, true);
            }
        }

        function onInventoryBarcodeScanned(code) {
            barcodeInput.value = code;
            matchAndSelectByBarcode(code);
        }

        updateStockPreview();
    </script>

    @include('components.barcode-scanner')
</x-app-layout>
