<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Edit Product
            </h2>

            <a href="{{ route('products.index') }}"
               class="bg-gray-600 text-white px-4 py-2 rounded">
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

            <div class="bg-white shadow rounded-lg p-6">

                <form action="{{ route('products.update', $product) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-4">
                        <label class="block mb-2">Category</label>

                        <select name="category_id" class="border rounded w-full p-2" required>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}"
                                    {{ $product->category_id == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block mb-2">Product Name</label>

                        <input
                            type="text"
                            name="name"
                            class="border rounded w-full p-2"
                            value="{{ old('name', $product->name) }}"
                            required>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block mb-2" for="size">Size</label>
                            <input type="text" name="size" id="size" class="border rounded w-full p-2"
                                   value="{{ old('size', $product->size) }}" placeholder="e.g. 25, 500">
                        </div>
                        <div>
                            <label class="block mb-2" for="unit">Unit</label>
                            <input type="text" name="unit" id="unit" list="product-units" class="border rounded w-full p-2"
                                   value="{{ old('unit', $product->unit) }}" placeholder="Choose or type a unit">
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
                            <input
                                type="text"
                                name="barcode"
                                id="barcode"
                                class="border rounded w-full p-2"
                                value="{{ old('barcode', $product->barcode) }}" inputmode="numeric">
                            <button type="button" onclick="openScanner('onEditProductBarcodeScanned')"
                                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded whitespace-nowrap">
                                Scan Barcode
                            </button>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block mb-2">Quantity</label>

                        <input type="number" class="border rounded w-full p-2 bg-gray-100" value="{{ $product->quantity }}" readonly>
                        <p class="text-sm text-gray-500 mt-1">Stock changes are recorded from Inventory.</p>
                    </div>

                    <div class="mb-4">
                        <label class="block mb-2">Price</label>

                        <input
                            type="number"
                            step="0.01"
                            name="price"
                            class="border rounded w-full p-2"
                            value="{{ old('price', $product->price) }}"
                            min="0"
                            required>
                    </div>

                    <div class="mb-4">
                        <label class="block mb-2">Expiry Date</label>

                        <input type="date" class="border rounded w-full p-2 bg-gray-100" value="{{ $product->expiry_date }}" readonly>
                        <p class="text-sm text-gray-500 mt-1">Expiry dates are managed per batch in Inventory.</p>
                    </div>

                    <button
                        type="submit"
                        class="bg-blue-600 text-white px-4 py-2 rounded">
                        Update Product
                    </button>

                    <a
                        href="{{ route('products.index') }}"
                        class="ml-3 text-gray-600">
                        Cancel
                    </a>

                </form>

            </div>

        </div>
    </div>

    @include('components.barcode-scanner')

    <script>
        function onEditProductBarcodeScanned(code) {
            const barcodeInput = document.getElementById('barcode');
            barcodeInput.value = code;
            barcodeInput.focus();
        }
    </script>
</x-app-layout>
