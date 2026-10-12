<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Transaction History</h2>
                <p class="text-sm text-gray-500 mt-1">Recorded sales, payment details, and purchased items.</p>
            </div>
            <a href="{{ route('sales.index') }}" class="bg-gray-700 hover:bg-gray-800 text-white px-4 py-2 rounded">Back to Sales</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded-lg p-5 mb-5">
                <form method="GET" action="{{ route('sales.history') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
                    <div class="md:col-span-2">
                        <label for="search" class="block text-sm font-medium text-gray-700">Search history</label>
                        <input id="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Transaction #, customer, or product" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div>
                        <label for="payment_method" class="block text-sm font-medium text-gray-700">Payment</label>
                        <select id="payment_method" name="payment_method" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">All methods</option>
                            @foreach(['Cash', 'GCash'] as $method)
                                <option value="{{ $method }}" @selected(($filters['payment_method'] ?? '') === $method)>{{ $method }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="date_from" class="block text-sm font-medium text-gray-700">From</label>
                        <input id="date_from" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div>
                        <label for="date_to" class="block text-sm font-medium text-gray-700">To</label>
                        <input id="date_to" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div class="md:col-span-5 flex gap-2">
                        <button class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded">Filter</button>
                        <a href="{{ route('sales.history') }}" class="border border-gray-300 text-gray-700 px-4 py-2 rounded">Clear</a>
                    </div>
                </form>
                @if($errors->any())
                    <p class="text-red-600 text-sm mt-3">{{ $errors->first() }}</p>
                @endif
            </div>

            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse min-w-[900px]">
                        <thead class="bg-gray-100 text-left">
                            <tr>
                                <th class="border p-3">Transaction</th>
                                <th class="border p-3">Status</th>
                                <th class="border p-3">Date / Time</th>
                                <th class="border p-3">Cashier</th>
                                <th class="border p-3">Customer</th>
                                <th class="border p-3">Payment</th>
                                <th class="border p-3">Items</th>
                                <th class="border p-3">Total</th>
                                <th class="border p-3">Received / Change</th>
                                <th class="border p-3">Receipt</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sales as $sale)
                                <tr class="hover:bg-gray-50">
                                    <td class="border p-3 font-medium">TXN-{{ str_pad($sale->id, 9, '0', STR_PAD_LEFT) }}</td>
                                    <td class="border p-3"><span class="px-2 py-1 rounded text-xs {{ $sale->status === 'voided' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}">{{ ucfirst($sale->status) }}</span></td>
                                    <td class="border p-3">{{ \Carbon\Carbon::parse($sale->sale_date)->format('M d, Y') }}<br><span class="text-xs text-gray-500">{{ $sale->created_at?->format('h:i A') }}</span></td>
                                    <td class="border p-3">{{ $sale->user->name ?? 'Legacy record' }}</td>
                                    <td class="border p-3">{{ $sale->customer_name ?: 'Walk-in' }}</td>
                                    <td class="border p-3">{{ $sale->payment_method ?: 'N/A' }}</td>
                                    <td class="border p-3">{{ $sale->items_count }}</td>
                                    <td class="border p-3 font-semibold">₱{{ number_format($sale->total_amount, 2) }}</td>
                                    <td class="border p-3">₱{{ number_format($sale->cash_tendered ?? 0, 2) }} / ₱{{ number_format($sale->change_amount ?? 0, 2) }}</td>
                                    <td class="border p-3 whitespace-nowrap">
                                        <a href="{{ route('sales.show', $sale) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded">View</a>
                                        @if(auth()->user()->role === 'owner' && $sale->status === 'completed')
                                            <form action="{{ route('sales.void', $sale) }}" method="POST" class="inline" onsubmit="const reason = window.prompt('Why are you voiding this transaction?'); if (!reason || !reason.trim()) return false; this.querySelector('[name=void_reason]').value = reason.trim(); return true;">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="void_reason">
                                                <button class="bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded">Void</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="10" class="text-center text-gray-500 p-8">No transactions match your filters.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-4">{{ $sales->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
