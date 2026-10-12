<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Add Product
            </h2>
            <a href="{{ route('products.index') }}"
               class="bg-gray-600 text-white px-4 py-2 rounded">
                Back
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div role="status" class="bg-green-100 text-green-800 p-4 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-red-100 text-red-700 p-4 rounded mb-4">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white shadow rounded-lg p-6">
                <form action="{{ route('products.store') }}" method="POST">
                    @csrf

                    <div class="mb-4">
                        <label class="block mb-2">Category</label>
                        <select name="category_id" id="category_id" class="border rounded w-full px-3 py-2" required>
                            <option value="" disabled {{ old('category_id') ? '' : 'selected' }}>Select a category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}"
                                    {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block mb-2">Product Name</label>
                        <input type="text" name="name" id="name"
                               value="{{ old('name') }}"
                               class="border rounded w-full px-3 py-2" required>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block mb-2" for="size">Size</label>
                            <input type="text" name="size" id="size" value="{{ old('size') }}"
                                   placeholder="e.g. 25, 500" class="border rounded w-full px-3 py-2">
                        </div>
                        <div>
                            <label class="block mb-2" for="unit">Unit</label>
                            <input type="text" name="unit" id="unit" list="product-units" value="{{ old('unit') }}"
                                   placeholder="Choose or type a unit" class="border rounded w-full px-3 py-2">
                            <datalist id="product-units">
                                @foreach(['g', 'kg', 'mg', 'ml', 'l', 'pcs', 'pack', 'bottle', 'can', 'sachet', 'box', 'bag'] as $unit)
                                    <option value="{{ $unit }}">
                                @endforeach
                            </datalist>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block mb-2">Barcode</label>
                        <div class="flex gap-2">
                            <input type="text" name="barcode" id="barcode"
                                   value="{{ old('barcode') }}"
                                   class="border rounded w-full px-3 py-2" inputmode="numeric">
                            <button type="button" onclick="openScanner('onProductBarcodeScanned')"
                                    class="bg-green-600 text-white px-4 py-2 rounded whitespace-nowrap">
                                Scan Barcode
                            </button>
                        </div>
                        <small id="barcode-feedback" class="text-sm"></small>
                        <small id="barcode-duplicate-feedback" class="block text-sm text-amber-700 mt-1" role="status"></small>
                        <small class="block text-xs text-gray-500 mt-1">Product suggestions from <a href="https://world.openfoodfacts.org" target="_blank" rel="noopener noreferrer" class="underline">Open Food Facts</a> (ODbL).</small>
                    </div>

                    <div class="mb-4">
                        <label class="block mb-2">Opening Quantity</label>
                        <input type="number" name="quantity" id="quantity"
                               value="{{ old('quantity', 0) }}"
                               class="border rounded w-full px-3 py-2" min="0" required>
                        <p class="text-sm text-gray-500 mt-1">Initial stock will be saved as an inventory movement.</p>
                    </div>

                    <div class="mb-4">
                        <label class="block mb-2">Opening Purchase Cost per Unit <span class="text-gray-400 text-sm">(optional)</span></label>
                        <input type="number" name="opening_unit_cost" min="0" step="0.01" value="{{ old('opening_unit_cost') }}" class="border rounded w-full px-3 py-2">
                    </div>

                    <div class="mb-4">
                        <label class="block mb-2">Price</label>
                        <input type="number" step="0.01" name="price" id="price"
                               value="{{ old('price') }}"
                               class="border rounded w-full px-3 py-2" min="0" required>
                    </div>

                    <div class="mb-4">
                        <label class="block mb-2">Expiry Date</label>
                        <input type="date" name="expiry_date" id="expiry_date"
                               value="{{ old('expiry_date') }}"
                               class="border rounded w-full px-3 py-2">
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">
                            Save Product
                        </button>
                        <a href="{{ route('products.index') }}" class="px-4 py-2 text-gray-600">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @include('components.barcode-scanner')

    <script>
function onProductBarcodeScanned(code) {
    const barcodeInput = document.getElementById('barcode');
    const feedback = document.getElementById('barcode-feedback');
    const nameInput = document.getElementById('name');
    const categorySelect = document.getElementById('category_id');

    barcodeInput.value = code;
    checkExistingProductBarcode();
    feedback.textContent = 'Barcode scanned. Looking up product details...';
    feedback.className = 'text-sm text-gray-500';

    fetch(`/products/lookup-barcode/${encodeURIComponent(code)}`)
        .then(response => response.json())
        .then(data => {
            if (!data.found) {
                feedback.textContent = data.offline
                    ? 'No saved product details are available offline. Enter the name and category manually.'
                    : 'Product not found in Open Food Facts. Enter the name and category manually.';
                feedback.className = 'text-sm text-blue-600';
                return;
            }

            if (data.name) nameInput.value = data.name;

            const normalize = value => value.toLowerCase().replace(/^en:/, '').replace(/[-_]/g, ' ').trim();
            const categories = (data.categories || []).map(normalize);
            const options = Array.from(categorySelect.options).filter(option => option.value);
            const matchedOption = options.find(option => categories.some(category =>
                normalize(option.textContent) === category
            )) || options.find(option => categories.some(category =>
                category.includes(normalize(option.textContent)) || normalize(option.textContent).includes(category)
            ));

            if (matchedOption) categorySelect.value = matchedOption.value;

            const categoryLabel = categories[0] || '';
            feedback.textContent = data.offline
                ? `Loaded saved product details${categoryLabel ? ` (category: ${categoryLabel})` : ''}.`
                : `Product details loaded${categoryLabel ? ` (Open Food Facts category: ${categoryLabel})` : ''}${matchedOption ? '; matching category selected.' : '; choose the closest category from your list.'}`;
            feedback.className = 'text-sm text-green-600';
            nameInput.focus();
        })
        .catch(() => {
            feedback.textContent = 'Could not look up this barcode. Enter the product name and category manually.';
            feedback.className = 'text-sm text-blue-600';
        });
}

const checkProductBarcodeUrl = @json(route('products.checkBarcode', ['barcode' => '__BARCODE__']));
const barcodeDuplicateFeedback = document.getElementById('barcode-duplicate-feedback');
let barcodeCheckTimer;

function checkExistingProductBarcode() {
    clearTimeout(barcodeCheckTimer);
    barcodeCheckTimer = setTimeout(async () => {
        const code = document.getElementById('barcode').value.trim();
        barcodeDuplicateFeedback.textContent = '';
        if (!code) return;

        try {
            const response = await fetch(checkProductBarcodeUrl.replace('__BARCODE__', encodeURIComponent(code)), {
                headers: { 'Accept': 'application/json' }
            });
            const data = await response.json();
            if (data.exists) {
                const names = (data.products || []).map(product => product.display_name).join(', ');
                barcodeDuplicateFeedback.textContent = `This barcode is already used by: ${names}. Verify the size and code; existing records are kept.`;
            }
        } catch (_) {
            // Barcode entry remains available if the local lookup request fails.
        }
    }, 250);
}

document.getElementById('barcode').addEventListener('input', checkExistingProductBarcode);
</script>
</x-app-layout>
