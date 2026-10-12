<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Products
            </h2>

            @if(auth()->user()->role === 'owner')
                <a href="{{ route('products.create') }}"
                   class="bg-blue-600 text-white px-4 py-2 rounded">
                    + Add Product
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div role="alert" class="bg-red-100 text-red-700 p-3 rounded mb-4">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white shadow rounded-lg p-6">

                <form method="GET" action="{{ route('products.index') }}" class="mb-4 flex flex-col md:flex-row gap-2"
                      data-search-suggestions data-suggestions-url="{{ route('products.suggestions') }}">
                    <input type="hidden" name="suggested_product_id" value="{{ $suggestedProductId }}">
                    <div class="relative w-full md:flex-1">
                    <input
                        type="text"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Search by product, size, unit, barcode, or category..."
                        autocomplete="off" aria-autocomplete="list"
                        class="border border-gray-300 rounded px-3 py-2 w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                    <div data-suggestions-menu class="hidden absolute z-50 left-0 right-0 top-full mt-1 max-h-72 overflow-y-auto rounded border border-gray-200 bg-white shadow-lg"></div>
                    </div>
                    <select name="category_id" class="border border-gray-300 rounded px-3 py-2 w-full md:w-56">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ (string) $categoryId === (string) $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                        Search
                    </button>
                    @if($search || $categoryId)
                        <a href="{{ route('products.index') }}"
                           class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded">
                            Clear
                        </a>
                    @endif
                </form>

                <div class="overflow-x-auto">
                <table class="w-full min-w-[850px] border-collapse border border-gray-300">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border p-2">No.</th>
                            <th class="border p-2">Category</th>
                            <th class="border p-2">Product</th>
                            <th class="border p-2">Size</th>
                            <th class="border p-2">Unit</th>
                            <th class="border p-2">Barcode</th>
                            <th class="border p-2">Quantity</th>
                            <th class="border p-2">Price</th>
                            <th class="border p-2">Expiry Date</th>
                            @if(auth()->user()->role === 'owner')
                                <th class="border p-2">Actions</th>
                            @endif
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($products as $product)
                            <tr>
                                <td class="border p-2">{{ $loop->iteration }}</td>
                                <td class="border p-2">{{ $product->category->name }}</td>
                                <td class="border p-2">{{ $product->display_name }}</td>
                                <td class="border p-2">{{ $product->size ?? '—' }}</td>
                                <td class="border p-2">{{ $product->unit ?? '—' }}</td>
                                <td class="border p-2">{{ $product->barcode }}</td>
                                <td class="border p-2">{{ $product->quantity }}</td>
                                <td class="border p-2">₱{{ number_format($product->price, 2) }}</td>
                                <td class="border p-2">{{ $product->expiry_date }}</td>

                                @if(auth()->user()->role === 'owner')
                                    <td class="border p-2">
                                        <a href="{{ route('products.edit', $product) }}"
                                           class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded transition duration-200">
                                            Edit
                                        </a>

                                        <form action="{{ route('products.destroy', $product) }}"
                                              method="POST"
                                              class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                onclick="return confirm('Delete this product? Products with sales or inventory history cannot be deleted.')"
                                                class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded">
                                                Delete
                                            </button>
                                        </form>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center p-4">
                                    No products found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
