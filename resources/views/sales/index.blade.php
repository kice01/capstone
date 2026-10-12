<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Sales</h2>
            <div class="flex gap-2">
                <a href="{{ route('sales.history') }}" class="bg-gray-700 hover:bg-gray-800 text-white px-4 py-2 rounded">Transaction History</a>
                @if(auth()->user()->role === 'owner')
                    <a href="{{ route('reports.sales') }}" class="bg-blue-600 hover:bg-indigo-700 text-white px-4 py-2 rounded shadow-sm border border-indigo-700">View Report</a>
                @endif
                <a href="{{ route('sales.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded">+ New Sale</a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="bg-green-100 text-green-700 p-3 rounded mb-4">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="bg-red-100 text-red-700 p-3 rounded mb-4">{{ session('error') }}</div>
            @endif

            <div class="bg-white shadow rounded-lg p-6 overflow-x-auto">
                <table class="w-full border-collapse border border-gray-300 min-w-[700px]">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border p-2">ID</th>
                            <th class="border p-2">Date</th>
                            <th class="border p-2">Customer</th>
                            <th class="border p-2">Payment</th>
                            <th class="border p-2">Items</th>
                            <th class="border p-2">Total</th>
                            <th class="border p-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sales as $sale)
                            <tr>
                                <td class="border p-2">{{ $sale->id }}</td>
                                <td class="border p-2">{{ $sale->sale_date }}</td>
                                <td class="border p-2">{{ $sale->customer_name ?? 'Walk-in' }}</td>
                                <td class="border p-2">{{ $sale->payment_method ?? 'N/A' }}</td>
                                <td class="border p-2">{{ $sale->items->count() }}</td>
                                <td class="border p-2">₱{{ number_format($sale->total_amount, 2) }}</td>
                                <td class="border p-2">
                                    <a href="{{ route('sales.show', $sale) }}" class="bg-blue-600 text-white px-3 py-1 rounded">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center p-4">No sales recorded yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
