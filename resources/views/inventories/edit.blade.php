<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Edit Inventory
            </h2>

            <a href="{{ route('inventories.index') }}"
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

                <form action="{{ route('inventories.update', $inventory) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-4">
                        <label class="block mb-2">Product</label>
                        <select name="product_id" class="border rounded w-full p-2" required>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}"
                                    {{ old('product_id', $inventory->product_id) == $product->id ? 'selected' : '' }}>
                                    {{ $product->display_name }} (Current qty: {{ $product->quantity }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block mb-2">Supplier</label>
                        <select name="supplier_id" class="border rounded w-full p-2" required>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}"
                                    {{ old('supplier_id', $inventory->supplier_id) == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block mb-2">Stock In (incoming quantity)</label>
                        <input type="number" name="stock_in" class="border rounded w-full p-2" value="{{ old('stock_in', $inventory->stock_in) }}" required>
                    </div>

                    <div class="mb-4">
                        <label class="block mb-2">Stock Out (outgoing/spoiled quantity)</label>
                        <input type="number" name="stock_out" class="border rounded w-full p-2" value="{{ old('stock_out', $inventory->stock_out) }}">
                    </div>

                    <div class="mb-4">
                        <label class="block mb-2">Expiry Date</label>
                        <input type="date" name="expiry_date" class="border rounded w-full p-2" value="{{ old('expiry_date', $inventory->expiry_date) }}">
                    </div>

                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">
                        Update Inventory
                    </button>

                    <a href="{{ route('inventories.index') }}" class="ml-3 text-gray-600">
                        Cancel
                    </a>

                </form>

            </div>

        </div>
    </div>
</x-app-layout>
